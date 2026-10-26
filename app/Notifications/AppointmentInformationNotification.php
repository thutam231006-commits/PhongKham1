<?php

namespace App\Notifications;

use App\Mail\AppointmentInformationMail;
use App\Models\Appointment;
use Illuminate\Notifications\Notification;

/**
 * Gửi thông tin buổi tư vấn cho khách hàng (email + thông báo trong hệ thống).
 *
 * Chỉ người nhận là khách hàng của lịch hẹn mới nhận được thông báo này —
 * người gửi (chuyên viên / lễ tân) không nhận bản sao.
 *
 * Cố tình KHÔNG đưa vào queue: thao tác gửi là thủ công nên người bấm cần
 * biết ngay kết quả (thành công hay lỗi cấu hình SMTP) thay vì để job treo.
 */
class AppointmentInformationNotification extends Notification
{
    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?string $extraNote = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): AppointmentInformationMail
    {
        return new AppointmentInformationMail($this->appointment, $this->extraNote);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $appointment = $this->appointment;

        return [
            'title' => 'Thông tin buổi tư vấn '.$appointment->appointment_code,
            'message' => 'Buổi tư vấn '.$appointment->service?->name.' lúc '
                .$appointment->start_at->format('H:i d/m/Y')
                .($this->extraNote ? '. Ghi chú: '.$this->extraNote : '.'),
            'type' => 'appointment.information',
            'url' => route('customer.appointments.detail', $appointment),
            'appointment_id' => $appointment->id,
        ];
    }
}
