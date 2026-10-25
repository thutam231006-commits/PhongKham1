<?php

namespace App\Notifications;

use App\Services\EmailVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Gửi mã xác thực 6 số kèm email — phương án thay thế cho việc bấm link.
 *
 * Email luôn chứa cả link kích hoạt lẫn mã số để khách có thể chọn cách
 * thuận tiện nhất: bấm link hoặc nhập mã trên website.
 */
class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose = EmailVerificationService::PURPOSE_ACTIVATION,
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
        $isActivation = $this->purpose === EmailVerificationService::PURPOSE_ACTIVATION;

        $mail = (new MailMessage)
            ->subject($isActivation ? 'Mã kích hoạt tài khoản — Mind Guide' : 'Mã xác thực email — Mind Guide')
            ->greeting('Xin chào '.$notifiable->name)
            ->line($isActivation
                ? 'Phòng khám đã tạo hồ sơ cho bạn. Dùng mã bên dưới để kích hoạt tài khoản và đặt mật khẩu riêng.'
                : 'Dùng mã bên dưới để xác thực địa chỉ email của bạn.');

        $mail->line('Mã xác thực của bạn: '.$this->code)
            ->line('Mã có hiệu lực trong '.config('auth.verification.otp_expire', 15).' phút và chỉ dùng được một lần.');

        $mail->action(
            $isActivation ? 'Nhập mã kích hoạt' : 'Nhập mã xác thực',
            route($isActivation ? 'account.activation.code' : 'verification.code')
        );

        return $mail->line('Phòng khám luôn bảo vệ thông tin riêng tư của bạn.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
