<?php

namespace App\Services;

use App\Exceptions\BookingSlotUnavailable;
use App\Models\Appointment;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentBookingService
{
    /** @var array<int, string> */
    private const HOLDING_STATUSES = ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
        private readonly ConsultationSetupService $consultationSetup,
    ) {}

    /**
     * @param  array{service_id:int, specialist_id:int, date:string, time:string, customer_note?:string|null, payment_method:string, consultation_type?:string, idempotency_key?:string|null}  $data
     */
    public function create(User $customer, array $data, ?User $actor = null): Appointment
    {
        $actor ??= $customer;

        return DB::transaction(function () use ($customer, $data, $actor): Appointment {
            if (! empty($data['idempotency_key'])) {
                $existing = Appointment::query()->where('customer_id', $customer->id)->where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    return $existing->load(['service', 'specialistProfile.user']);
                }
            }

            $service = Service::query()->active()->findOrFail($data['service_id']);
            $specialist = SpecialistProfile::query()
                ->where('status', 'ACTIVE')
                ->with('services')
                ->lockForUpdate()
                ->findOrFail($data['specialist_id']);

            if (! $specialist->services->contains('id', $service->id)) {
                throw new BookingSlotUnavailable('SPECIALIST_SERVICE_MISMATCH', 'Chuyên viên không cung cấp dịch vụ đã chọn.');
            }

            $startAt = $this->parseStartAt($data['date'], $data['time']);
            $now = CarbonImmutable::now();

            $leadHours = (int) SystemSetting::valueOf('min_booking_lead_hours', 2);
            $advanceDays = (int) SystemSetting::valueOf('max_advance_booking_days', 30);
            if ($startAt->lt($now->addHours($leadHours)) || $startAt->gt($now->addDays($advanceDays))) {
                throw new BookingSlotUnavailable('BOOKING_OUT_OF_WINDOW', "Khung giờ phải cách hiện tại ít nhất {$leadHours} giờ và không quá {$advanceDays} ngày.");
            }

            if (! $this->matchesWorkingSchedule($specialist, $startAt, $service->duration_minutes)) {
                throw new BookingSlotUnavailable('SLOT_UNAVAILABLE', 'Khung giờ đã chọn không nằm trong lịch làm việc của chuyên viên.');
            }

            $maxActive = (int) SystemSetting::valueOf('max_active_appointments', 3);
            if ($customer->appointments()->holdingSlot()->where('start_at', '>=', $now)->count() >= $maxActive) {
                throw new BookingSlotUnavailable('BOOKING_LIMIT_EXCEEDED', "Bạn đã đạt giới hạn {$maxActive} lịch hẹn đang hoạt động.");
            }

            $endAt = $startAt->addMinutes($service->duration_minutes);
            $conflict = Appointment::query()
                ->where('specialist_profile_id', $specialist->id)
                ->holdingSlot()
                ->where('start_at', '<', $endAt->addMinutes($service->buffer_minutes))
                ->where('end_at', '>', $startAt->subMinutes($service->buffer_minutes))
                ->exists();

            if ($conflict) {
                throw new BookingSlotUnavailable('APPOINTMENT_SLOT_UNAVAILABLE', 'Khung giờ vừa được khách khác đặt. Vui lòng chọn khung giờ khác.');
            }

            $customerConflict = $customer->appointments()->holdingSlot()->where('start_at', '<', $endAt)->where('end_at', '>', $startAt)->exists();
            if ($customerConflict) {
                throw new BookingSlotUnavailable('CUSTOMER_SCHEDULE_CONFLICT', 'Bạn đã có lịch hẹn trùng khung giờ này.');
            }

            $isOperatorBooking = in_array(strtolower((string) $actor->role), ['staff', 'admin'], true);
            $consultationType = $data['consultation_type'] ?? 'OFFLINE';
            $status = $isOperatorBooking ? 'CONFIRMED' : 'PENDING';
            $appointment = Appointment::query()->create([
                'appointment_code' => $this->generateAppointmentCode(),
                'customer_id' => $customer->id,
                'specialist_profile_id' => $specialist->id,
                'service_id' => $service->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration_minutes' => $service->duration_minutes,
                'buffer_minutes' => $service->buffer_minutes,
                'price' => $service->price,
                'status' => $status,
                'payment_status' => 'UNPAID',
                'payment_method' => $data['payment_method'],
                'payment_expires_at' => null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'source' => $isOperatorBooking ? 'ONSITE' : 'ONLINE',
                'consultation_type' => $consultationType,
                'customer_note' => $data['customer_note'] ?? null,
                'created_by' => $actor->id,
                'slot_lock_key' => $specialist->id.'|'.$startAt->format('Y-m-d H:i:s'),
            ]);

            $appointment->statusHistory()->create([
                'to_status' => $status,
                'action' => 'create',
                'actor_id' => $actor->id,
                'actor_role' => strtoupper((string) $actor->role),
            ]);

            if ($isOperatorBooking) {
                $details = $this->consultationSetup->detailsForOnsiteBooking($appointment);
                $appointment->update($details);
                if ($details['needs_attention']) {
                    $appointment->update([
                        'needs_attention' => true,
                        'attention_reason' => AttentionService::REASON_NO_ROOM,
                        'attention_flagged_at' => now(),
                    ]);
                }
            }

            $this->audit->record('appointment.created', $appointment, new: ['status' => $status, 'payment_method' => $data['payment_method']], actor: $actor);
            if ($isOperatorBooking) {
                $this->notifications->send($customer, 'Đặt lịch thành công', 'Mã lịch của bạn: '.$appointment->appointment_code.'.', 'appointment.created', route('customer.appointments.detail', $appointment), $appointment);
            } else {
                $this->notifications->send($customer, 'Đã tiếp nhận yêu cầu đặt lịch', 'Phòng khám sẽ gọi điện cho bạn trong vòng 24 giờ để xác nhận lịch '.$appointment->appointment_code.'.', 'appointment.pending_confirmation', route('customer.appointments.detail', $appointment), $appointment);
                $this->notifications->sendMany(User::query()->whereIn('role', ['staff', 'admin'])->where('status', 'ACTIVE')->get(), 'Lịch mới chờ xác nhận', 'Lịch '.$appointment->appointment_code.' của '.$customer->name.' đang chờ gọi xác nhận.', 'appointment.attention', route('staff.appointments'), $appointment);
            }

            return $appointment->load(['service', 'specialistProfile.user']);
        }, 3);
    }

    private function parseStartAt(string $date, string $time): CarbonImmutable
    {
        $start = trim(explode('-', $time, 2)[0]);
        $parsed = CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$start);

        if (! $parsed || $parsed->format('Y-m-d H:i') !== $date.' '.$start) {
            throw new BookingSlotUnavailable('INVALID_SLOT', 'Khung giờ không hợp lệ.');
        }

        return $parsed;
    }

    private function matchesWorkingSchedule(SpecialistProfile $specialist, CarbonImmutable $startAt, int $durationMinutes): bool
    {
        $endAt = $startAt->addMinutes($durationMinutes);

        $blocked = ScheduleException::query()
            ->where(function ($query) use ($specialist): void {
                $query->whereNull('specialist_profile_id')->orWhere('specialist_profile_id', $specialist->id);
            })
            ->whereDate('start_date', '<=', $startAt)
            ->whereDate('end_date', '>=', $startAt)
            ->get()
            ->contains(function (ScheduleException $exception) use ($startAt, $endAt): bool {
                if (! $exception->start_time || ! $exception->end_time) {
                    return true;
                }
                $blockedStart = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$exception->start_time);
                $blockedEnd = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$exception->end_time);

                return $startAt->lt($blockedEnd) && $endAt->gt($blockedStart);
            });

        if ($blocked) {
            return false;
        }

        return $specialist->workingSchedules()
            ->where('day_of_week', $startAt->isoWeekday())
            ->where('is_active', true)
            ->where(function ($query) use ($startAt): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $startAt);
            })
            ->where(function ($query) use ($startAt): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $startAt);
            })
            ->get()
            ->contains(function ($schedule) use ($startAt, $endAt): bool {
                $windowStart = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$schedule->start_time);
                $windowEnd = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$schedule->end_time);

                if ($startAt->lt($windowStart) || $endAt->gt($windowEnd)) {
                    return false;
                }

                if ($schedule->break_start && $schedule->break_end) {
                    $breakStart = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$schedule->break_start);
                    $breakEnd = CarbonImmutable::parse($startAt->format('Y-m-d').' '.$schedule->break_end);

                    return $endAt->lte($breakStart) || $startAt->gte($breakEnd);
                }

                return true;
            });
    }

    private function generateAppointmentCode(): string
    {
        do {
            $code = 'MG'.now()->format('ymd').Str::upper(Str::random(5));
        } while (Appointment::query()->where('appointment_code', $code)->exists());

        return $code;
    }
}
