<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Support\CustomerGender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  '24_HOURS'|'30_MINUTES'  $reminderType
     */
    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $reminderType = '24_HOURS',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reminderType === '30_MINUTES'
                ? 'Buổi tư vấn của bạn bắt đầu sau 30 phút — '.$this->appointment->appointment_code
                : 'Nhắc lịch tư vấn ngày mai — '.$this->appointment->appointment_code,
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['customer.customerProfile', 'service', 'specialistProfile.user', 'consultationRoom']);
        $customer = $this->appointment->customer;
        $profile = $customer?->customerProfile;

        return new Content(
            markdown: 'emails.appointment-reminder',
            with: [
                'appointment' => $this->appointment,
                'reminderType' => $this->reminderType,
                'customerName' => $customer?->name,
                'phone' => $customer?->phone,
                'email' => $customer?->email,
                'dateOfBirth' => $profile?->date_of_birth?->format('d/m/Y'),
                'gender' => $customer?->gender ? CustomerGender::label($customer->gender) : null,
                'address' => $customer?->address,
                'serviceName' => $this->appointment->service?->name,
                'specialistName' => $this->appointment->specialistProfile?->user?->name,
                'startAt' => $this->appointment->start_at?->format('d/m/Y H:i'),
                'location' => $this->appointment->consultation_type === 'ONLINE'
                    ? 'Trực tuyến — '.$this->appointment->meeting_url
                    : $this->appointment->consultationRoom?->name,
                'detailUrl' => route('customer.appointments.detail', $this->appointment),
            ],
        );
    }
}
