<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * SlotAvailabilityService — nguồn sự thật duy nhất cho khung giờ trống (Yêu cầu 1 & 3).
 *
 * Toàn bộ màn hình đặt lịch, đặt lịch tại quầy và API đều dùng chung service này
 * để danh sách khung giờ khớp chính xác với điều kiện mà
 * {@see AppointmentBookingService} kiểm tra lúc ghi — nhờ đó khách không bao giờ
 * chọn xong mới báo hết slot.
 */
class SlotAvailabilityService
{
    /**
     * Khung giờ trống của một chuyên viên trong ngày.
     *
     * @return array<int, array{start: string, end: string, available: bool}>
     */
    public function slotsFor(SpecialistProfile $specialist, Service $service, string $date): array
    {
        $day = CarbonImmutable::parse($date);
        $now = CarbonImmutable::instance(now());
        $leadHours = (int) SystemSetting::valueOf('min_booking_lead_hours', 2);
        $advanceDays = (int) SystemSetting::valueOf('max_advance_booking_days', 30);

        if ($day->lt($now->startOfDay()) || $day->gt($now->addDays($advanceDays)->endOfDay())) {
            return [];
        }

        $exceptions = $this->exceptionsFor($specialist, $date);
        if ($exceptions->contains(fn (ScheduleException $item): bool => ! $item->start_time)) {
            return [];
        }

        $windows = $specialist->workingSchedules()
            ->where('day_of_week', $day->isoWeekday())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $day))
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))
            ->orderBy('start_time')
            ->get();

        if ($windows->isEmpty()) {
            return [];
        }

        $busy = $this->busyWindowsFor($specialist, $date);
        $earliestStart = $now->addHours($leadHours);
        $step = max(1, $service->duration_minutes + $service->buffer_minutes);
        $slots = [];

        foreach ($windows as $window) {
            $cursor = CarbonImmutable::parse($date.' '.$window->start_time);
            $windowEnd = CarbonImmutable::parse($date.' '.$window->end_time);

            while ($cursor->addMinutes($service->duration_minutes)->lte($windowEnd)) {
                $end = $cursor->addMinutes($service->duration_minutes);

                $slots[] = [
                    'start' => $cursor->format('H:i'),
                    'end' => $end->format('H:i'),
                    'available' => $this->isFree($cursor, $end, $exceptions, $busy, $earliestStart),
                ];

                $cursor = $cursor->addMinutes($step);
            }
        }

        return $slots;
    }

    /**
     * Chỉ trả về các khung giờ còn trống thực sự — dùng cho UI và API.
     *
     * @return array<int, array{start: string, end: string, available: bool}>
     */
    public function openSlotsFor(SpecialistProfile $specialist, Service $service, string $date): array
    {
        return array_values(array_filter(
            $this->slotsFor($specialist, $service, $date),
            fn (array $slot): bool => $slot['available'],
        ));
    }

    public function isSlotOpen(SpecialistProfile $specialist, Service $service, string $date, string $time): bool
    {
        $start = trim(explode('-', $time, 2)[0]);

        return collect($this->openSlotsFor($specialist, $service, $date))
            ->contains(fn (array $slot): bool => $slot['start'] === $start);
    }

    /**
     * Tổng quan tải lịch theo chuyên viên cho một ngày — phục vụ màn hình nhân viên.
     *
     * @return array{patients: int, booked: int, open: int, capacity: int}
     */
    public function dailyLoadFor(SpecialistProfile $specialist, string $date, ?Service $service = null): array
    {
        $service ??= $specialist->services()->active()->orderBy('duration_minutes')->first();

        if (! $service instanceof Service) {
            return ['patients' => 0, 'booked' => 0, 'open' => 0, 'capacity' => 0];
        }

        $slots = $this->slotsFor($specialist, $service, $date);
        $booked = collect($slots)->reject(fn (array $slot): bool => $slot['available'])->count();

        return [
            'patients' => Appointment::query()
                ->where('specialist_profile_id', $specialist->id)
                ->whereDate('start_at', $date)
                ->whereNotIn('status', ['CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC'])
                ->distinct('customer_id')
                ->count('customer_id'),
            'booked' => $booked,
            'open' => count($slots) - $booked,
            'capacity' => count($slots),
        ];
    }

    /** @return Collection<int, ScheduleException> */
    private function exceptionsFor(SpecialistProfile $specialist, string $date): Collection
    {
        return ScheduleException::query()
            ->where(fn ($query) => $query->whereNull('specialist_profile_id')->orWhere('specialist_profile_id', $specialist->id))
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();
    }

    /**
     * Các khoảng đã bị chiếm bởi lịch hẹn đang giữ slot (bao gồm buffer hai bên).
     *
     * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function busyWindowsFor(SpecialistProfile $specialist, string $date): array
    {
        return Appointment::query()
            ->where('specialist_profile_id', $specialist->id)
            ->holdingSlot()
            ->whereDate('start_at', $date)
            ->get(['start_at', 'end_at', 'buffer_minutes'])
            ->map(fn (Appointment $appointment): array => [
                CarbonImmutable::instance($appointment->start_at)->subMinutes((int) $appointment->buffer_minutes),
                CarbonImmutable::instance($appointment->end_at)->addMinutes((int) $appointment->buffer_minutes),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, ScheduleException>  $exceptions
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $busy
     */
    private function isFree(CarbonImmutable $start, CarbonImmutable $end, Collection $exceptions, array $busy, CarbonImmutable $earliestStart): bool
    {
        if ($start->lt($earliestStart)) {
            return false;
        }

        foreach ($exceptions as $exception) {
            if (! $exception->start_time || ! $exception->end_time) {
                return false;
            }

            $blockedStart = CarbonImmutable::parse($start->format('Y-m-d').' '.$exception->start_time);
            $blockedEnd = CarbonImmutable::parse($start->format('Y-m-d').' '.$exception->end_time);

            if ($start->lt($blockedEnd) && $end->gt($blockedStart)) {
                return false;
            }
        }

        foreach ($busy as [$busyStart, $busyEnd]) {
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                return false;
            }
        }

        return true;
    }
}
