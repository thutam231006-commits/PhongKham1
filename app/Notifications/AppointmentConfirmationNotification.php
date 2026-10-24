<?php

namespace App\Notifications;

use App\Mail\AppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Gửi xác nhận đặt lịch qua email (Yêu cầu 4) đồng thời ghi thông báo trong hệ thống.
 */
class AppointmentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Appointment $appointment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Xác nhận đặt lịch '.$this->appointment->appointment_code.' — '.config('app.name'))
            ->greeting('Xin chào '.$notifiable->name)
            ->line('Phòng khám đã ghi nhận yêu cầu đặt lịch '.$this->appointment->service->name.' của bạn.')
            ->line('Thời gian: '.$this->appointment->start_at->format('d/m/Y H:i').' – '.$this->appointment->end_at->format('H:i'))
            ->line('Chuyên viên: '.$this->appointment->specialistProfile->user->name)
            ->action('Xem chi tiết lịch hẹn', route('customer.appointments.detail', $this->appointment))
            ->line('Phòng khám sẽ gọi xác nhận trong vòng 24 giờ.');
    }

    /**
     * Bản markdown tuỳ chỉnh — hiển thị đầy đủ thông tin lịch hẹn và địa điểm.
     */
    public function toMailMarkdown(object $notifiable): mixed
    {
        return new AppointmentConfirmationMail($this->appointment);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Đã tiếp nhận yêu cầu đặt lịch',
            'message' => 'Phòng khám sẽ gọi cho bạn trong vòng 24 giờ để xác nhận lịch '.$this->appointment->appointment_code.'.',
            'type' => 'appointment.confirmation_email',
            'url' => route('customer.appointments.detail', $this->appointment),
            'appointment_id' => $this->appointment->id,
        ];
    }
}
