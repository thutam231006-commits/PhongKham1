<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

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
        return (new MailMessage)
            ->subject('Kích hoạt tài khoản Mind Guide')
            ->greeting('Xin chào '.$notifiable->name)
            ->line('Phòng khám đã tạo lịch hẹn cho bạn. Hãy kích hoạt tài khoản để theo dõi lịch tại nhà.')
            ->action('Kích hoạt tài khoản', route('account.activation.form', ['token' => $this->token]))
            ->line('Liên kết có hiệu lực trong 24 giờ và chỉ sử dụng được một lần.');
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
