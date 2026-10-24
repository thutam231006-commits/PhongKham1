<?php

namespace App\Support;

/**
 * CustomerGender — giới tính khách hàng (NFR-PRV-003 đã được nới lỏng).
 *
 * Trường này tồn tại để phòng khám liên hệ và nhận diện khách đúng cách khi
 * gửi lời nhắc lịch; khách có thể để trống nếu không muốn cung cấp.
 */
final class CustomerGender
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'MALE' => 'Nam',
            'FEMALE' => 'Nữ',
            'OTHER' => 'Khác',
        ];
    }

    public static function label(?string $value): string
    {
        return self::options()[$value ?? ''] ?? 'Chưa cập nhật';
    }
}
