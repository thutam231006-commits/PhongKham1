<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use \Illuminate\Bus\Queueable;

    public function __construct(
        private readonly string $token,
        private readonly ?string $code = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Kích hoạt tài khoản Mind Guide')
            ->greeting('Xin chào '.$notifiable->name)
            ->line('Phòng khám đã tạo lịch hẹn cho bạn. Hãy kích hoạt tài khoản để theo dõi lịch tại nhà.')
            ->action('Kích hoạt tài khoản', route('account.activation.form', ['token' => $this->token]))
            ->line('Liên kết có hiệu lực trong 24 giờ và chỉ sử dụng được một lần.');

        if (filled($this->code)) {
            $mail->line('Nếu bạn không bấm được liên kết, hãy dùng mã xác thực bên dưới tại trang nhập mã: '.$this->code)
                ->line('Mã có hiệu lực trong '.config('auth.verification.otp_expire', 15).' phút và chỉ dùng được một lần.');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
