<?php

use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('appointments:maintain', function (AppointmentWorkflowService $workflow, NotificationService $notifications) {
    $expired = $workflow->expirePending();
    $reminders = Appointment::query()
        ->where('status', 'CONFIRMED')
        ->whereNull('reminded_at')
        ->whereBetween('start_at', [now()->addHours(23), now()->addHours(24)])
        ->with('customer')
        ->get();

    foreach ($reminders as $appointment) {
        $notifications->send($appointment->customer, 'Nhắc lịch hẹn', 'Bạn có lịch hẹn '.$appointment->appointment_code.' vào '.$appointment->start_at->format('d/m/Y H:i').'.', 'appointment.reminder', route('customer.appointments.detail', $appointment), $appointment);
        $appointment->update(['reminded_at' => now()]);
    }

    $this->info("Đã hết hạn {$expired} lịch và gửi {$reminders->count()} lời nhắc.");
})->purpose('Hết hạn lịch chờ thanh toán và gửi nhắc lịch');

Schedule::command('appointments:maintain')->everyFifteenMinutes()->withoutOverlapping();
