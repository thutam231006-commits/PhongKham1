<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PaymentTransaction;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly AuditService $audit, private readonly NotificationService $notifications) {}

    /** @param array{external_id:string,amount:int|float,content?:string,transfer_type?:string,payload?:array<string,mixed>} $data */
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

            if (($data['transfer_type'] ?? 'in') === 'in' && $appointment) {
                $status = (int) $appointment->price === (int) $data['amount'] ? 'MATCHED' : 'AMOUNT_MISMATCH';
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
