<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Support\AppointmentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Thông tin buổi tư vấn gửi cho khách hàng.
 *
 * Được kích hoạt thủ công từ module chuyên viên / lễ tân khi khách cần được
 * nhắc lại đầy đủ thông tin buổi hẹn (mã lịch, thời gian, địa điểm, hình thức,
 * chuyên viên, lưu ý chuẩn bị). Email chỉ gửi tới địa chỉ của khách hàng.
 */
class AppointmentInformationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?string $extraNote = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông tin buổi tư vấn '.$this->appointment->appointment_code.' — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing([
            'customer.customerProfile',
            'service',
            'specialistProfile.user',
            'consultationRoom',
        ]);

        $appointment = $this->appointment;
        $isOnline = $appointment->consultation_type === 'ONLINE';

        return new Content(
            markdown: 'emails.appointment-information',
            with: [
                'appointment' => $appointment,
                'customerName' => $appointment->customer?->name,
                'serviceName' => $appointment->service?->name,
                'specialistName' => $appointment->specialistProfile?->user?->name,
                'specialistTitle' => $appointment->specialistProfile?->title,
                'startAt' => $appointment->start_at?->format('d/m/Y H:i'),
                'endAt' => $appointment->end_at?->format('H:i'),
                'durationMinutes' => $appointment->duration_minutes,
                'statusLabel' => AppointmentStatus::label((string) $appointment->status),
                'isOnline' => $isOnline,
                'location' => $isOnline
                    ? ($appointment->meeting_url ?: 'Phòng khám sẽ gửi link phòng họp trực tuyến trước giờ hẹn.')
                    : ($appointment->consultationRoom?->name ?: 'Phòng khám sẽ thông báo số phòng khi bạn check-in.'),
                'extraNote' => $this->extraNote,
                'detailUrl' => route('customer.appointments.detail', $appointment),
                'hotline' => '1900 1567',
            ],
        );
    }
}
