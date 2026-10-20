<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PaymentTransaction;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(private readonly AuditService $audit, private readonly NotificationService $notifications) {}

    /** @param array{external_id:string,amount:int|float,account_number?:string,content?:string,transfer_type?:string,payload?:array<string,mixed>} $data */
    public function reconcile(array $data, string $provider = 'SEPAY'): PaymentTransaction
    {
        return DB::transaction(function () use ($data, $provider): PaymentTransaction {
            $existing = PaymentTransaction::query()->where('external_id', $data['external_id'])->first();
            if ($existing) {
                return $existing;
            }

            preg_match('/DATLICH([A-Z0-9-]+)/i', (string) ($data['content'] ?? ''), $matches);
            $appointment = isset($matches[1]) ? Appointment::query()->where('appointment_code', $matches[1])->lockForUpdate()->first() : null;
            $status = 'UNMATCHED';

            $expectedAccount = preg_replace('/\s+/', '', (string) config('services.sepay.bank_account'));
            $receivedAccount = preg_replace('/\s+/', '', (string) ($data['account_number'] ?? ''));

            if ($expectedAccount !== '' && ! hash_equals($expectedAccount, $receivedAccount)) {
                $status = 'ACCOUNT_MISMATCH';
            } elseif (($data['transfer_type'] ?? 'in') === 'in' && $appointment) {
                if ($appointment->status === 'PENDING' && $appointment->payment_expires_at?->isPast()) {
                    $status = 'EXPIRED_PAYMENT';
                } else {
                    $status = (int) $appointment->price === (int) $data['amount'] ? 'MATCHED' : 'AMOUNT_MISMATCH';
                }
                if ($status === 'MATCHED' && in_array($appointment->status, ['PENDING', 'CONFIRMED'], true)) {
                    $from = $appointment->status;
                    $appointment->update(['payment_status' => 'PAID', 'paid_at' => now(), 'status' => 'CONFIRMED', 'confirmed_at' => $appointment->confirmed_at ?? now()]);
                    if ($from !== 'CONFIRMED') {
                        $appointment->statusHistory()->create(['from_status' => $from, 'to_status' => 'CONFIRMED', 'action' => 'payment_confirmed', 'actor_role' => 'SYSTEM']);
                    }
                    $this->notifications->send($appointment->customer, 'Thanh toán thành công', 'Lịch '.$appointment->appointment_code.' đã được xác nhận.', 'payment.paid', route('customer.appointments.detail', $appointment), $appointment);
                }
            }

            $transaction = PaymentTransaction::query()->create([
                'appointment_id' => $appointment?->id,
                'provider' => $provider,
                'external_id' => $data['external_id'],
                'transfer_type' => $data['transfer_type'] ?? 'in',
                'amount' => $data['amount'],
                'content' => $data['content'] ?? null,
                'status' => $status,
                'payload' => $data['payload'] ?? $data,
                'processed_at' => now(),
            ]);
            $this->audit->record('payment.reconciled', $transaction, new: ['status' => $status, 'appointment_id' => $appointment?->id]);

            return $transaction;
        });
    }

    /** @return array{qr_url:string,transfer_content:string,bank_code:string,bank_account:string,account_name:string,amount:int,expires_at:?string} */
    public function paymentInstructions(Appointment $appointment): array
    {
        $bankCode = trim((string) config('services.sepay.bank_code'));
        $bankAccount = preg_replace('/\s+/', '', (string) config('services.sepay.bank_account'));
        $accountName = trim((string) config('services.sepay.account_name'));

        if ($bankCode === '' || $bankAccount === '' || $accountName === '') {
            throw new RuntimeException('Cấu hình tài khoản nhận thanh toán SePay chưa đầy đủ.');
        }

        $transferContent = 'SEVQR DATLICH'.$appointment->appointment_code;
        $query = http_build_query([
            'acc' => $bankAccount,
            'bank' => $bankCode,
            'amount' => (int) $appointment->price,
            'des' => $transferContent,
            'template' => 'compact',
            'showinfo' => 'true',
            'holder' => $accountName,
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'qr_url' => rtrim((string) config('services.sepay.qr_base_url'), '?').'?'.$query,
            'transfer_content' => $transferContent,
            'bank_code' => $bankCode,
            'bank_account' => $bankAccount,
            'account_name' => $accountName,
            'amount' => (int) $appointment->price,
            'expires_at' => $appointment->payment_expires_at?->toIso8601String(),
        ];
    }

    public function expirePendingPayment(Appointment $appointment): Appointment
    {
        if ($appointment->status !== 'PENDING' || $appointment->payment_status !== 'UNPAID' || ! $appointment->payment_expires_at?->isPast()) {
            return $appointment;
        }

        return DB::transaction(function () use ($appointment): Appointment {
            $locked = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            if ($locked->status !== 'PENDING' || $locked->payment_status !== 'UNPAID' || ! $locked->payment_expires_at?->isPast()) {
                return $locked;
            }

            $locked->update([
                'status' => 'CANCELLED_BY_CLINIC',
                'cancelled_at' => now(),
                'cancel_reason' => 'Hết thời gian giữ chỗ thanh toán SePay.',
                'slot_lock_key' => null,
            ]);
            $locked->statusHistory()->create([
                'from_status' => 'PENDING',
                'to_status' => 'CANCELLED_BY_CLINIC',
                'action' => 'payment_expired',
                'actor_role' => 'SYSTEM',
            ]);
            $this->audit->record('payment.expired', $locked, old: ['status' => 'PENDING'], new: ['status' => 'CANCELLED_BY_CLINIC']);

            return $locked->refresh();
        });
    }

    public function markRefunded(RefundRequest $refund, User $actor, string $reference): RefundRequest
    {
        return DB::transaction(function () use ($refund, $actor, $reference): RefundRequest {
            $refund = RefundRequest::query()->lockForUpdate()->findOrFail($refund->id);
            abort_unless($refund->status === 'PENDING', 409, 'Yêu cầu hoàn tiền đã được xử lý.');
            $refund->update(['status' => 'REFUNDED', 'reference' => $reference, 'processed_by' => $actor->id, 'processed_at' => now()]);
            $refund->appointment()->update(['payment_status' => 'REFUNDED', 'refund_status' => 'REFUNDED', 'refunded_at' => now(), 'refund_amount' => $refund->amount]);
            $this->audit->record('payment.refunded', $refund, old: ['status' => 'PENDING'], new: ['status' => 'REFUNDED', 'reference' => $reference]);
            $this->notifications->send($refund->appointment->customer, 'Đã hoàn tiền', 'Yêu cầu hoàn tiền cho lịch '.$refund->appointment->appointment_code.' đã hoàn tất.', 'payment.refunded', route('customer.appointments.detail', $refund->appointment), $refund->appointment);

            return $refund->refresh();
        });
    }
}
