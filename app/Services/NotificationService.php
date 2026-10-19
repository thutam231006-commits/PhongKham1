<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\GenericDatabaseNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function send(User $recipient, string $title, string $message, string $type, ?string $url = null, ?Appointment $appointment = null): void
    {
        $recipient->notify(new GenericDatabaseNotification([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'url' => $url,
            'appointment_id' => $appointment?->id,
        ]));
    }

    /** @param Collection<int, User> $recipients */
    public function sendMany(Collection $recipients, string $title, string $message, string $type, ?string $url = null, ?Appointment $appointment = null): void
    {
        $recipients->each(fn (User $recipient) => $this->send($recipient, $title, $message, $type, $url, $appointment));
    }
}
