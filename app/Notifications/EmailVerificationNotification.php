<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email xác thực địa chỉ email cho khách đăng ký tự do.
 *
 * Một email duy nhất chứa cả hai cách xác thực để khách chọn:
 *  - bấm link kích hoạt;
 *  - nhập mã 6 số tại trang nhập mã.
 */
class EmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly string $verificationUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Xác thực email — Mind Guide')
            ->greeting('Xin chào '.$notifiable->name)
            ->line('Cảm ơn bạn đã đăng ký tài khoản tại Mind Guide. Để xác thực địa chỉ email, bạn có thể bấm nút bên dưới hoặc nhập mã 6 số.')
            ->action('Xác thực email ngay', $this->verificationUrl)
            ->line('Hoặc nhập mã xác thực của bạn: '.$this->code)
            ->line('Mã có hiệu lực trong '.config('auth.verification.otp_expire', 15).' phút và chỉ dùng được một lần.')
            ->line('Link xác thực có hiệu lực trong '.config('auth.verification.expire', 60).' phút.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
