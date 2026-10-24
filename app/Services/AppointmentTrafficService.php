<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * AppointmentTrafficService — theo dõi lưu lượng đặt lịch theo thời gian (Yêu cầu 1).
 *
 * Tất cả số liệu đều dựng từ bảng `appointments` sẵn có nên không phát sinh
 * bảng mới; dữ liệu không chứa thông tin định danh hay nội dung tư vấn.
 */
class AppointmentTrafficService
{
    /**
     * Số lịch đặt theo từng khung giờ trong ngày (dùng để nhân viên và admin
     * nhìn thấy giờ cao điểm của lịch bác sĩ).
     *
     * Gom nhóm trong PHP thay vì dùng hàm giờ của MySQL để chạy được trên cả
     * MySQL lẫn SQLite (môi trường test).
     *
     * @return Collection<int, array{hour: int, total: int, completed: int, cancelled: int, pending: int}>
     */
    public function byHourOfDay(string $date): Collection
    {
        $appointments = Appointment::query()
            ->whereDate('start_at', $date)
            ->get(['start_at', 'status']);

        $buckets = [];

        foreach ($appointments as $appointment) {
            $hour = (int) $appointment->start_at->format('G');
            $buckets[$hour] ??= ['total' => 0, 'completed' => 0, 'cancelled' => 0, 'pending' => 0];
            $buckets[$hour]['total']++;

            match ($appointment->status) {
                'COMPLETED' => $buckets[$hour]['completed']++,
                'CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC', 'NO_SHOW' => $buckets[$hour]['cancelled']++,
                'PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED' => $buckets[$hour]['pending']++,
                default => null,
            };
        }

        return collect(range(7, 21))->map(fn (int $hour): array => [
            'hour' => $hour,
            'total' => $buckets[$hour]['total'] ?? 0,
            'completed' => $buckets[$hour]['completed'] ?? 0,
            'cancelled' => $buckets[$hour]['cancelled'] ?? 0,
            'pending' => $buckets[$hour]['pending'] ?? 0,
        ]);
    }

    /**
     * Số bệnh nhân theo ngày trong khoảng thời gian — dùng cho biểu đồ lưu lượng.
     *
     * Gom nhóm trong PHP để không phụ thuộc hàm ngày của MySQL.
     *
     * @return Collection<int, array{day: string, total: int, new_patients: int, cancelled: int}>
     */
    public function dailyPatients(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $appointments = Appointment::query()
            ->whereBetween('start_at', [$from, $to])
            ->get(['start_at', 'status', 'customer_id']);

        $buckets = [];

        foreach ($appointments as $appointment) {
            $day = $appointment->start_at->format('Y-m-d');
            $buckets[$day] ??= ['total' => 0, 'patients' => [], 'cancelled' => 0];
            $buckets[$day]['total']++;
            $buckets[$day]['patients'][$appointment->customer_id] = true;

            if (in_array($appointment->status, ['CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC', 'NO_SHOW'], true)) {
                $buckets[$day]['cancelled']++;
            }
        }

        $days = collect();
        for ($day = $from->startOfDay(); $day->lte($to->startOfDay()); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days->push([
                'day' => $key,
                'total' => $buckets[$key]['total'] ?? 0,
                'new_patients' => count($buckets[$key]['patients'] ?? []),
                'cancelled' => $buckets[$key]['cancelled'] ?? 0,
            ]);
        }

        return $days;
    }
}
