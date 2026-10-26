<?php

namespace App\Services;

use App\Exceptions\BookingSlotUnavailable;
use App\Models\Appointment;
use App\Models\RefundRequest;
use App\Models\RescheduleRequest;
use App\Models\ScheduleException;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Notifications\AppointmentInformationNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentWorkflowService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
        private readonly ConsultationSetupService $consultationSetup,
        private readonly MailHealthService $mailHealth,
    ) {}

    public function confirm(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'PENDING', 409, 'Chỉ lịch đang chờ mới có thể xác nhận.');
            $appointment->load(['customer', 'specialistProfile.user']);
            $details = $this->consultationSetup->detailsFor($appointment);
            $this->transition($appointment, 'CONFIRMED', 'confirm', $actor, [...$details, 'confirmed_at' => now(), 'payment_expires_at' => null]);
            $this->notifications->send($appointment->customer, 'Lịch hẹn đã được xác nhận', 'Lịch '.$appointment->appointment_code.' đã được xác nhận. Vui lòng xem thông tin phòng hoặc link phòng họp trực tuyến trong chi tiết lịch.', 'appointment.confirmed', route('customer.appointments.detail', $appointment), $appointment);
            $this->notifications->send($appointment->specialistProfile->user, 'Có lịch hẹn mới', 'Lịch '.$appointment->appointment_code.' đã được xác nhận với khách '.$appointment->customer->name.'.', 'appointment.confirmed', route('specialist.appointments.detail', $appointment), $appointment);

            return $appointment->refresh();
        });
    }

    public function reassignSpecialist(Appointment $appointment, SpecialistProfile $specialist, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $specialist, $actor): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'PENDING', 409, 'Chỉ lịch đang chờ xác nhận mới có thể chuyển chuyên viên.');

            $specialist = SpecialistProfile::query()->where('status', 'ACTIVE')->with('services')->lockForUpdate()->findOrFail($specialist->id);
            abort_unless($specialist->services->contains('id', $appointment->service_id), 422, 'Chuyên viên mới không cung cấp dịch vụ đã chọn.');
            $this->assertSpecialistWorking($specialist, $appointment);

            $conflict = Appointment::query()
                ->where('specialist_profile_id', $specialist->id)
                ->whereKeyNot($appointment->id)
                ->holdingSlot()
                ->where('start_at', '<', $appointment->end_at->copy()->addMinutes($appointment->buffer_minutes))
                ->where('end_at', '>', $appointment->start_at->copy()->subMinutes($appointment->buffer_minutes))
                ->exists();
            abort_unless(! $conflict, 409, 'Chuyên viên mới đã có lịch trùng khung giờ.');

            $oldSpecialist = $appointment->specialistProfile()->with('user')->first();
            $appointment->update([
                'specialist_profile_id' => $specialist->id,
                'slot_lock_key' => $specialist->id.'|'.$appointment->start_at->format('Y-m-d H:i:s'),
            ]);
            $appointment->statusHistory()->create([
                'from_status' => 'PENDING',
                'to_status' => 'PENDING',
                'action' => 'reassign_specialist',
                'actor_id' => $actor->id,
                'actor_role' => strtoupper((string) $actor->role),
                'reason' => 'Chuyển từ '.($oldSpecialist?->user?->name ?? 'chuyên viên cũ').' sang '.$specialist->user->name,
            ]);
            $this->audit->record('appointment.reassign_specialist', $appointment, old: ['specialist_profile_id' => $oldSpecialist?->id], new: ['specialist_profile_id' => $specialist->id], actor: $actor);
            $this->notifications->send($appointment->customer, 'Lịch đã được điều phối lại', 'Phòng khám đã chuyển lịch '.$appointment->appointment_code.' sang chuyên viên '.$specialist->user->name.'.', 'appointment.reassigned', route('customer.appointments.detail', $appointment), $appointment);
            $this->notifications->send($specialist->user, 'Có lịch hẹn chờ xác nhận', 'Lịch '.$appointment->appointment_code.' đang chờ xác nhận với bạn lúc '.$appointment->start_at->format('d/m/Y H:i').'.', 'appointment.attention', route('specialist.appointments.detail', $appointment), $appointment);

            return $appointment->refresh();
        });
    }

    /** @param array{bank_name?:string|null,bank_account?:string|null,account_holder?:string|null} $bank */
    public function cancelByCustomer(Appointment $appointment, User $customer, ?string $reason, array $bank = []): Appointment
    {
        abort_unless($appointment->customer_id === $customer->id, 404);

        return DB::transaction(function () use ($appointment, $customer, $reason, $bank): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless(in_array($appointment->status, ['PENDING', 'CONFIRMED'], true) && now()->lt($appointment->start_at), 409, 'Lịch hẹn không còn có thể hủy.');
            $percentage = $this->refundPercentage($appointment);
            $refundAmount = $appointment->payment_status === 'PAID' ? (int) floor((int) $appointment->price * $percentage / 100) : 0;
            $attributes = ['cancelled_at' => now(), 'cancel_reason' => $reason, 'slot_lock_key' => null];

            if ($refundAmount > 0) {
                RefundRequest::query()->updateOrCreate(['appointment_id' => $appointment->id], [...$bank, 'amount' => $refundAmount, 'percentage' => $percentage, 'status' => 'PENDING']);
                $attributes += ['payment_status' => 'REFUND_PENDING', 'refund_status' => 'REFUND_PENDING', 'refund_amount' => $refundAmount];
            }

            $this->transition($appointment, 'CANCELLED_BY_CUSTOMER', 'cancel_by_customer', $customer, $attributes, $reason);
            $this->notifyOperators('Khách hủy lịch', 'Lịch '.$appointment->appointment_code.' đã được khách hủy.', $appointment);

            return $appointment->refresh();
        });
    }

    public function cancelByClinic(Appointment $appointment, User $actor, string $reason): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor, $reason): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless(in_array($appointment->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'], true), 409, 'Lịch hẹn không còn có thể hủy.');
            $attributes = ['cancelled_at' => now(), 'cancel_reason' => $reason, 'slot_lock_key' => null, 'needs_attention' => false, 'attention_reason' => null, 'attention_flagged_at' => null];

            if ($appointment->payment_status === 'PAID') {
                RefundRequest::query()->updateOrCreate(['appointment_id' => $appointment->id], ['amount' => (int) $appointment->price, 'percentage' => 100, 'status' => 'PENDING']);
                $attributes += ['payment_status' => 'REFUND_PENDING', 'refund_status' => 'REFUND_PENDING', 'refund_amount' => $appointment->price];
            }

            $this->transition($appointment, 'CANCELLED_BY_CLINIC', 'cancel_by_clinic', $actor, $attributes, $reason);
            $this->notifications->send($appointment->customer, 'Phòng khám đã hủy lịch', 'Lịch '.$appointment->appointment_code.' đã bị hủy. Lý do: '.$reason, 'appointment.cancelled', route('customer.appointments.detail', $appointment), $appointment);

            return $appointment->refresh();
        });
    }

    public function requestReschedule(Appointment $appointment, User $customer, CarbonImmutable $requestedStartAt, ?string $reason): RescheduleRequest
    {
        abort_unless($appointment->customer_id === $customer->id, 404);
        abort_unless($appointment->status === 'CONFIRMED' && $appointment->start_at->gte(now()->addHours(24)) && $appointment->reschedule_count < 2, 409, 'Lịch hẹn không đủ điều kiện đổi lịch.');
        $requestedEndAt = $requestedStartAt->addMinutes($appointment->duration_minutes);
        $this->assertSlotAvailable($appointment, $requestedStartAt, $requestedEndAt);

        return DB::transaction(function () use ($appointment, $customer, $requestedStartAt, $requestedEndAt, $reason): RescheduleRequest {
            $request = RescheduleRequest::query()->create(['appointment_id' => $appointment->id, 'requested_start_at' => $requestedStartAt, 'requested_end_at' => $requestedEndAt, 'reason' => $reason, 'requested_by' => $customer->id]);
            $this->transition($appointment, 'RESCHEDULE_REQUESTED', 'request_reschedule', $customer, [], $reason);
            $this->notifyOperators('Yêu cầu đổi lịch', 'Khách yêu cầu đổi lịch '.$appointment->appointment_code.'.', $appointment);

            return $request;
        });
    }

    public function resolveReschedule(RescheduleRequest $request, User $actor, bool $approved, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($request, $actor, $approved, $reason): Appointment {
            $request = RescheduleRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($request->status === 'PENDING', 409, 'Yêu cầu đổi lịch đã được xử lý.');
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($request->appointment_id);

            if (! $approved) {
                $request->update(['status' => 'REJECTED', 'resolved_by' => $actor->id, 'resolved_at' => now(), 'reason' => $reason ?? $request->reason]);
                $this->transition($appointment, 'CONFIRMED', 'reject_reschedule', $actor, [], $reason);

                return $appointment->refresh();
            }

            $startAt = CarbonImmutable::instance($request->requested_start_at);
            $endAt = CarbonImmutable::instance($request->requested_end_at);
            $this->assertSlotAvailable($appointment, $startAt, $endAt);
            $newAppointment = Appointment::query()->create([
                ...$appointment->only(['customer_id', 'specialist_profile_id', 'service_id', 'duration_minutes', 'buffer_minutes', 'price', 'payment_status', 'paid_at', 'payment_method', 'customer_note', 'consultation_type']),
                'appointment_code' => 'MG'.now()->format('ymd').strtoupper(str()->random(5)),
                'rescheduled_from_id' => $appointment->id,
                'reschedule_count' => $appointment->reschedule_count + 1,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'CONFIRMED',
                'source' => 'RESCHEDULE',
                'created_by' => $actor->id,
                'slot_lock_key' => $appointment->specialist_profile_id.'|'.$startAt->format('Y-m-d H:i:s'),
                'confirmed_at' => now(),
            ]);
            $newAppointment->update($this->consultationSetup->detailsFor($newAppointment));
            $newAppointment->statusHistory()->create(['to_status' => 'CONFIRMED', 'action' => 'reschedule_approved', 'actor_id' => $actor->id, 'actor_role' => strtoupper($actor->role)]);
            $request->update(['status' => 'APPROVED', 'resolved_by' => $actor->id, 'resolved_at' => now()]);
            $this->transition($appointment, 'RESCHEDULED', 'approve_reschedule', $actor, ['slot_lock_key' => null], $reason);
            $this->notifications->send($appointment->customer, 'Đổi lịch thành công', 'Lịch mới của bạn là '.$newAppointment->appointment_code.'.', 'appointment.rescheduled', route('customer.appointments.detail', $newAppointment), $newAppointment);

            return $newAppointment;
        });
    }

    public function expirePending(): int
    {
        $expired = Appointment::query()->where('status', 'PENDING')->whereNotNull('payment_expires_at')->where('payment_expires_at', '<=', now())->get();
        foreach ($expired as $appointment) {
            DB::transaction(function () use ($appointment): void {
                $appointment->update(['status' => 'CANCELLED_BY_CLINIC', 'slot_lock_key' => null, 'cancelled_at' => now(), 'cancel_reason' => 'Hết thời gian giữ chỗ thanh toán.']);
                $appointment->statusHistory()->create(['from_status' => 'PENDING', 'to_status' => 'CANCELLED_BY_CLINIC', 'action' => 'payment_expired', 'actor_role' => 'SYSTEM']);
                $this->audit->record('appointment.payment_expired', $appointment, old: ['status' => 'PENDING'], new: ['status' => 'CANCELLED_BY_CLINIC']);
            });
        }

        return $expired->count();
    }

    /**
     * Bắt đầu buổi tư vấn (Staff điều phối hộ — SCR-STF-03).
     */
    public function start(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'CONFIRMED', 409, 'Chỉ lịch đã xác nhận mới có thể bắt đầu.');
            abort_unless(now()->gte($appointment->start_at->copy()->subMinutes(15)), 422, 'Chỉ có thể bắt đầu từ 15 phút trước giờ hẹn.');

            $this->transition($appointment, 'IN_PROGRESS', 'start', $actor, ['started_at' => now()]);

            return $appointment->refresh();
        });
    }

    /**
     * Đánh dấu vắng mặt (Staff điều phối hộ — SCR-STF-03).
     */
    public function markNoShow(Appointment $appointment, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless($appointment->status === 'CONFIRMED', 409, 'Lịch hẹn không ở trạng thái có thể đánh dấu vắng mặt.');
            abort_unless(now()->gte($appointment->start_at->copy()->addMinutes(15)), 422, 'Chỉ đánh dấu vắng mặt sau 15 phút chờ.');

            $this->transition($appointment, 'NO_SHOW', 'no_show', $actor, ['slot_lock_key' => null, 'needs_attention' => false, 'attention_reason' => null, 'attention_flagged_at' => null]);
            $this->notifications->send($appointment->customer, 'Lịch hẹn đã kết thúc', 'Lịch '.$appointment->appointment_code.' đã được đánh dấu vắng mặt. Vui lòng liên hệ phòng khám để đặt lịch mới.', 'appointment.no_show', route('customer.appointments.detail', $appointment), $appointment);

            return $appointment->refresh();
        });
    }

    /**
     * Đổi lịch do phòng khám thực hiện (SCR-STF-03 / SCR-STF-07).
     *
     * Tạo lịch mới ở trạng thái CONFIRMED, chuyển lịch cũ sang RESCHEDULED và
     * xóa cờ "cần xử lý" sau khi đã xử lý xong.
     */
    public function rescheduleByClinic(Appointment $appointment, CarbonImmutable $requestedStartAt, User $actor, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $requestedStartAt, $actor, $reason): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            abort_unless(in_array($appointment->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'], true), 409, 'Lịch hẹn không còn có thể đổi.');

            $requestedEndAt = $requestedStartAt->addMinutes($appointment->duration_minutes);
            $this->assertSlotAvailable($appointment, $requestedStartAt, $requestedEndAt);

            $newAppointment = Appointment::query()->create([
                ...$appointment->only(['customer_id', 'specialist_profile_id', 'service_id', 'duration_minutes', 'buffer_minutes', 'price', 'payment_status', 'paid_at', 'payment_method', 'customer_note', 'consultation_type']),
                'appointment_code' => 'MG'.now()->format('ymd').strtoupper(str()->random(5)),
                'rescheduled_from_id' => $appointment->id,
                'reschedule_count' => $appointment->reschedule_count + 1,
                'start_at' => $requestedStartAt,
                'end_at' => $requestedEndAt,
                'status' => 'CONFIRMED',
                'source' => 'STAFF',
                'created_by' => $actor->id,
                'slot_lock_key' => $appointment->specialist_profile_id.'|'.$requestedStartAt->format('Y-m-d H:i:s'),
                'confirmed_at' => now(),
            ]);
            $newAppointment->update($this->consultationSetup->detailsFor($newAppointment));
            $newAppointment->statusHistory()->create(['to_status' => 'CONFIRMED', 'action' => 'reschedule_by_clinic', 'actor_id' => $actor->id, 'actor_role' => strtoupper($actor->role), 'reason' => $reason]);
            $this->transition($appointment, 'RESCHEDULED', 'reschedule_by_clinic', $actor, ['slot_lock_key' => null, 'needs_attention' => false, 'attention_reason' => null, 'attention_flagged_at' => null], $reason);
            $this->notifications->send($appointment->customer, 'Lịch hẹn đã được đổi', 'Phòng khám đã đổi lịch '.$appointment->appointment_code.' sang '.$requestedStartAt->format('d/m/Y H:i').'. Lịch mới: '.$newAppointment->appointment_code.'.', 'appointment.rescheduled', route('customer.appointments.detail', $newAppointment), $newAppointment);

            return $newAppointment;
        });
    }

    /**
     * Gửi thông báo thủ công tới khách của lịch (SCR-STF-03 / FR-NOTI-005).
     */
    public function notifyCustomer(Appointment $appointment, User $actor, string $message): void
    {
        $appointment->loadMissing('customer');
        $this->notifications->send($appointment->customer, 'Thông báo từ phòng khám', $message, 'appointment.manual_notice', route('customer.appointments.detail', $appointment), $appointment);
        $this->audit->record('appointment.manual_notice', $appointment, new: ['message' => $message], actor: $actor);
    }

    /**
     * Gửi thông tin buổi tư vấn cho khách hàng (chỉ khách nhận, không gửi bản sao
     * cho người thực hiện). Dùng cho thao tác thủ công từ module chuyên viên.
     *
     * @return array{sent:bool, message:string}
     */
    public function sendSessionInformation(Appointment $appointment, User $actor, ?string $note = null): array
    {
        $appointment->loadMissing(['customer', 'service', 'specialistProfile.user', 'consultationRoom']);

        $customer = $appointment->customer;

        if (! $customer) {
            return ['sent' => false, 'message' => 'Lịch hẹn không có thông tin khách hàng để gửi.'];
        }

        if (blank($customer->email)) {
            return ['sent' => false, 'message' => 'Khách hàng chưa có địa chỉ email nên không thể gửi thông tin buổi tư vấn.'];
        }

        $configuration = $this->mailHealth->inspectConfiguration();

        if ($configuration['blocking']) {
            return ['sent' => false, 'message' => $configuration['message']];
        }

        try {
            $customer->notify(new AppointmentInformationNotification($appointment, $note));
        } catch (Throwable $exception) {
            Log::error('Không gửi được thông tin buổi tư vấn.', [
                'appointment_id' => $appointment->id,
                'error' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'message' => $this->mailHealth->describeException($exception)];
        }

        $this->audit->record(
            'appointment.information_sent',
            $appointment,
            new: ['to' => $customer->email, 'note' => $note],
            actor: $actor,
        );

        return [
            'sent' => true,
            'message' => 'Đã gửi thông tin buổi tư vấn tới '.$customer->email.($configuration['ok'] ? '' : ' Lưu ý: '.$configuration['message']),
        ];
    }

    private function transition(Appointment $appointment, string $to, string $action, User $actor, array $attributes = [], ?string $reason = null): void
    {
        $from = $appointment->status;
        $appointment->update([...$attributes, 'status' => $to]);
        $appointment->statusHistory()->create(['from_status' => $from, 'to_status' => $to, 'action' => $action, 'actor_id' => $actor->id, 'actor_role' => strtoupper($actor->role), 'reason' => $reason]);
        $this->audit->record('appointment.'.$action, $appointment, old: ['status' => $from], new: ['status' => $to]);
    }

    private function assertSpecialistWorking(SpecialistProfile $specialist, Appointment $appointment): void
    {
        $blocked = ScheduleException::query()
            ->where(function ($query) use ($specialist): void {
                $query->whereNull('specialist_profile_id')->orWhere('specialist_profile_id', $specialist->id);
            })
            ->whereDate('start_date', '<=', $appointment->start_at)
            ->whereDate('end_date', '>=', $appointment->start_at)
            ->get()
            ->contains(function (ScheduleException $exception) use ($appointment): bool {
                if (! $exception->start_time || ! $exception->end_time) {
                    return true;
                }

                $blockedStart = CarbonImmutable::parse($appointment->start_at->format('Y-m-d').' '.$exception->start_time);
                $blockedEnd = CarbonImmutable::parse($appointment->start_at->format('Y-m-d').' '.$exception->end_time);

                return $appointment->start_at->lt($blockedEnd) && $appointment->end_at->gt($blockedStart);
            });
        abort_unless(! $blocked, 409, 'Chuyên viên mới nghỉ hoặc bị khóa trong khung giờ này.');

        $working = $specialist->workingSchedules()
            ->where('day_of_week', $appointment->start_at->isoWeekday())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $appointment->start_at))
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $appointment->start_at))
            ->get()
            ->contains(function ($schedule) use ($appointment): bool {
                $start = CarbonImmutable::parse($appointment->start_at->format('Y-m-d').' '.$schedule->start_time);
                $end = CarbonImmutable::parse($appointment->start_at->format('Y-m-d').' '.$schedule->end_time);

                return $appointment->start_at->gte($start) && $appointment->end_at->lte($end);
            });
        abort_unless($working, 409, 'Chuyên viên mới không làm việc trong khung giờ này.');
    }

    private function assertSlotAvailable(Appointment $appointment, CarbonImmutable $startAt, CarbonImmutable $endAt): void
    {
        $conflict = Appointment::query()->where('specialist_profile_id', $appointment->specialist_profile_id)->whereKeyNot($appointment->id)->holdingSlot()->where('start_at', '<', $endAt->addMinutes($appointment->buffer_minutes))->where('end_at', '>', $startAt->subMinutes($appointment->buffer_minutes))->exists();
        if ($conflict) {
            throw new BookingSlotUnavailable('APPOINTMENT_SLOT_UNAVAILABLE', 'Khung giờ vừa được khách khác đặt.');
        }
    }

    private function refundPercentage(Appointment $appointment): int
    {
        $hours = now()->diffInHours($appointment->start_at, false);

        return $hours >= 24 ? 100 : ($hours >= 12 ? 50 : 0);
    }

    private function notifyOperators(string $title, string $message, Appointment $appointment): void
    {
        $this->notifications->sendMany(User::query()->whereIn('role', ['staff', 'admin'])->where('status', 'ACTIVE')->get(), $title, $message, 'appointment.attention', null, $appointment);
    }
}
