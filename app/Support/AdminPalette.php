<?php

namespace App\Support;

/**
 * AdminPalette — bảng màu dùng chung cho biểu đồ và chip trạng thái ở khu quản trị.
 *
 * Màu chỉ là tín hiệu phụ: mọi chip/biểu đồ luôn kèm nhãn chữ theo NFR-ACC-001.
 */
final class AdminPalette
{
    /** @return array<string, string> */
    public static function appointmentStatus(): array
    {
        return [
            'PENDING' => '#D97706',
            'CONFIRMED' => '#2D82C4',
            'RESCHEDULE_REQUESTED' => '#8B5CF6',
            'RESCHEDULED' => '#64748B',
            'IN_PROGRESS' => '#0EA5E9',
            'COMPLETED' => '#16A34A',
            'CANCELLED_BY_CUSTOMER' => '#94A3B8',
            'CANCELLED_BY_CLINIC' => '#DC2626',
            'NO_SHOW' => '#B91C1C',
        ];
    }

    /** @return array<string, string> */
    public static function paymentStatus(): array
    {
        return [
            'UNPAID' => '#D97706',
            'PAID' => '#16A34A',
            'REFUND_PENDING' => '#EA580C',
            'REFUNDED' => '#64748B',
            'NOT_REQUIRED' => '#94A3B8',
        ];
    }

    /** @return array<string, string> */
    public static function reviewStatus(): array
    {
        return [
            'PENDING_REVIEW' => '#D97706',
            'VISIBLE' => '#16A34A',
            'HIDDEN' => '#DC2626',
            'RESTORED' => '#2D82C4',
            'REPORTED' => '#EA580C',
        ];
    }

    /** @return array<string, string> */
    public static function accountStatus(): array
    {
        return [
            'ACTIVE' => '#16A34A',
            'PENDING_VERIFICATION' => '#D97706',
            'LOCKED' => '#DC2626',
            'DISABLED' => '#64748B',
        ];
    }

    /** @return array<string, string> */
    public static function role(): array
    {
        return [
            'admin' => '#7C3AED',
            'staff' => '#2D82C4',
            'specialist' => '#0891B2',
            'customer' => '#64748B',
        ];
    }

    public static function forAppointmentStatus(string $status): string
    {
        return self::appointmentStatus()[$status] ?? '#94A3B8';
    }

    public static function forPaymentStatus(string $status): string
    {
        return self::paymentStatus()[$status] ?? '#94A3B8';
    }

    public static function forReviewStatus(string $status): string
    {
        return self::reviewStatus()[$status] ?? '#94A3B8';
    }

    public static function forAccountStatus(string $status): string
    {
        return self::accountStatus()[$status] ?? '#94A3B8';
    }

    public static function forRole(string $role): string
    {
        return self::role()[$role] ?? '#94A3B8';
    }
}
