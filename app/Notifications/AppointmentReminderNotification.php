<?php

namespace App\Notifications;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Support\CustomerGender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Nhắc lịch tự động (Yêu cầu 2): lần 1 trước 24 giờ, lần 2 trước 30 phút.
 *
 * Gửi qua cả email và thông báo trong hệ thống; email kèm đầy đủ thông tin
 * khách hàng (họ tên, ngày sinh, giới tính, số điện thoại, email, địa chỉ).
 */
class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  '24_HOURS'|'30_MINUTES'  $reminderType
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $reminderType = '24_HOURS',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isSoon = $this->reminderType === '30_MINUTES';
        $customer = $this->appointment->customer;
        $profile = $customer?->customerProfile;

        return (new MailMessage)
            ->subject(($isSoon ? 'Buổi tư vấn bắt đầu sau 30 phút' : 'Nhắc lịch tư vấn ngày mai').' — '.$this->appointment->appointment_code)
            ->greeting('Xin chào '.$notifiable->name)
            ->line($isSoon
                ? 'Buổi tư vấn của bạn sẽ bắt đầu sau 30 phút.'
                : 'Bạn có lịch tư vấn vào ngày mai.')
            ->line('Gói dịch vụ: '.$this->appointment->service->name)
            ->line('Chuyên viên: '.$this->appointment->specialistProfile->user->name)
            ->line('Thời gian: '.$this->appointment->start_at->format('d/m/Y H:i'))
            ->line('---')
            ->line('Thông tin khách hàng đã đăng ký:')
            ->line('Họ và tên: '.($notifiable->name ?: 'Chưa cập nhật'))
            ->line('Ngày sinh: '.($profile?->date_of_birth?->format('d/m/Y') ?: 'Chưa cập nhật'))
            ->line('Giới tính: '.CustomerGender::label($customer?->gender))
            ->line('Số điện thoại: '.($customer?->phone ?: 'Chưa cập nhật'))
            ->line('Email: '.($customer?->email ?: 'Chưa cập nhật'))
            ->line('Địa chỉ: '.($customer?->address ?: 'Chưa cập nhật'))
            ->action('Xem chi tiết lịch hẹn', route('customer.appointments.detail', $this->appointment));
    }

    /**
     * Bản markdown tuỳ chỉnh.
     */
    public function toMailMarkdown(object $notifiable): mixed
    {
        return new AppointmentReminderMail($this->appointment, $this->reminderType);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isSoon = $this->reminderType === '30_MINUTES';

        return [
            'title' => $isSoon ? 'Sắp đến giờ tư vấn' : 'Nhắc lịch hẹn ngày mai',
            'message' => $isSoon
                ? 'Buổi tư vấn '.$this->appointment->appointment_code.' bắt đầu lúc '.$this->appointment->start_at->format('H:i').'. Vui lòng đến trước 10 phút.'
                : 'Bạn có lịch hẹn '.$this->appointment->appointment_code.' vào '.$this->appointment->start_at->format('d/m/Y H:i').'.',
            'type' => $isSoon ? 'appointment.reminder_30m' : 'appointment.reminder_24h',
            'url' => route('customer.appointments.detail', $this->appointment),
            'appointment_id' => $this->appointment->id,
        ];
    }
}
