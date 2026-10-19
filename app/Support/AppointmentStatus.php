<?php

namespace App\Support;

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
            'IN_PROGRESS' => 'Đang tư vấn',
            'COMPLETED' => 'Hoàn thành',
            'CANCELLED_BY_CUSTOMER' => 'Khách đã hủy',
            'CANCELLED_BY_CLINIC' => 'Phòng khám hủy',
            'NO_SHOW' => 'Vắng mặt',
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
