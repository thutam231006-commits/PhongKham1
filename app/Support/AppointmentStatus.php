<?php

namespace App\Support;

/**
 * AppointmentStatus — nhãn tiếng Việt thống nhất cho trạng thái lịch hẹn.
 *
 * Căn cứ: docs/ThietKeGiaoDien.md §1.8 (bảng trạng thái lịch hẹn) và §2.1
 * (bảng mã màu chip trạng thái). Nhãn luôn kèm màu — không dùng màu làm dấu
 * hiệu duy nhất (NFR-ACC-001).
 */
final class AppointmentStatus
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'PENDING' => 'Chờ thanh toán',
            'CONFIRMED' => 'Đã xác nhận',
            'RESCHEDULE_REQUESTED' => 'Chờ duyệt đổi lịch',
            'RESCHEDULED' => 'Đã đổi lịch',
            'IN_PROGRESS' => 'Đang diễn ra',
            'COMPLETED' => 'Đã hoàn thành',
            'CANCELLED_BY_CUSTOMER' => 'Khách đã hủy',
            'CANCELLED_BY_CLINIC' => 'Phòng khám đã hủy',
            'NO_SHOW' => 'Vắng mặt',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }

    /**
     * Trạng thái còn có thể thao tác điều phối (SCR-STF-03).
     *
     * @return array<int, string>
     */
    public static function actionable(): array
    {
        return ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'];
    }
}
