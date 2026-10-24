<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận đặt lịch '.$this->appointment->appointment_code.' — Mind Guide',
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['customer', 'service', 'specialistProfile.user', 'consultationRoom']);

        return new Content(
            markdown: 'emails.appointment-confirmation',
            with: [
                'appointment' => $this->appointment,
                'customerName' => $this->appointment->customer?->name,
                'specialistName' => $this->appointment->specialistProfile?->user?->name,
                'serviceName' => $this->appointment->service?->name,
                'startAt' => $this->appointment->start_at?->format('d/m/Y H:i'),
                'endAt' => $this->appointment->end_at?->format('H:i'),
                'location' => $this->appointment->consultation_type === 'ONLINE'
                    ? 'Trực tuyến — '.$this->appointment->meeting_url
                    : $this->appointment->consultationRoom?->name,
                'detailUrl' => route('customer.appointments.detail', $this->appointment),
            ],
        );
    }
}
