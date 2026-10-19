<?php

namespace App\Notifications;

use App\Models\SystemSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericDatabaseNotification extends Notification
{
    /** @param array{title:string,message:string,type:string,url?:string|null,appointment_id?:int|null} $payload */
    public function __construct(private readonly array $payload) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return SystemSetting::valueOf('email_notifications_enabled', false) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->payload['title'])->line($this->payload['message']);
        if (! empty($this->payload['url'])) {
            $mail->action('Xem chi tiết', $this->payload['url']);
        }

        return $mail->line('Mind Guide luôn bảo vệ thông tin riêng tư của bạn.');
    }

    /** @return array{title:string,message:string,type:string,url?:string|null,appointment_id?:int|null} */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
