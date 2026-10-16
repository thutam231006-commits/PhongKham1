<?php

namespace App\Services;

use App\Exceptions\BookingSlotUnavailable;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentBookingService
{
    /** @var array<int, string> */
    private const HOLDING_STATUSES = ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS'];

    /**
     * @param  array{service_id:int, specialist_id:int, date:string, time:string, customer_note?:string|null, payment_method:string}  $data
     */
    public function create(User $customer, array $data): Appointment
    {
        return DB::transaction(function () use ($customer, $data): Appointment {
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

            if ($startAt->lt($now->addHours(2)) || $startAt->gt($now->addDays(30))) {
                throw new BookingSlotUnavailable('BOOKING_OUT_OF_WINDOW', 'Khung giờ phải cách hiện tại ít nhất 2 giờ và không quá 30 ngày.');
            }

            if (! $this->matchesWorkingSchedule($specialist, $startAt, $service->duration_minutes)) {
                throw new BookingSlotUnavailable('SLOT_UNAVAILABLE', 'Khung giờ đã chọn không nằm trong lịch làm việc của chuyên viên.');
            }

            if ($customer->appointments()->holdingSlot()->where('start_at', '>=', $now)->count() >= 3) {
                throw new BookingSlotUnavailable('BOOKING_LIMIT_EXCEEDED', 'Bạn đã đạt giới hạn 3 lịch hẹn đang hoạt động.');
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

            $status = $data['payment_method'] === 'CASH' ? 'CONFIRMED' : 'PENDING';
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
                'source' => 'ONLINE',
                'customer_note' => $data['customer_note'] ?? null,
                'created_by' => $customer->id,
                'slot_lock_key' => $specialist->id.'|'.$startAt->format('Y-m-d H:i:s'),
            ]);

            $appointment->statusHistory()->create([
                'to_status' => $status,
                'action' => 'create',
                'actor_id' => $customer->id,
                'actor_role' => 'CUSTOMER',
            ]);

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
