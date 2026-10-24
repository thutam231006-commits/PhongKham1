<?php

namespace App\Services;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentReminderService — nhắc lịch tự động (Yêu cầu 2).
 *
 * - Lần 1: trước 24 giờ buổi hẹn.
 * - Lần 2: trước 30 phút buổi hẹn.
 *
 * Mỗi mốc được đánh dấu bằng cột riêng nên không thể gửi trùng kể cả khi
 * lệnh chạy lại nhiều lần trong cùng một chu kỳ.
 */
class AppointmentReminderService
{
    /**
     * Gửi lời nhắc cho các lịch đã tới mốc nhắc.
     *
     * @return array{24_HOURS: int, 30_MINUTES: int}
     */
    public function sendDue(): array
    {
        return [
            '24_HOURS' => $this->sendBatch('24_HOURS', 'reminder_24h_at', [now()->addHours(24)->subMinutes(10), now()->addHours(24)->addMinutes(10)]),
            '30_MINUTES' => $this->sendBatch('30_MINUTES', 'reminder_30m_at', [now()->addMinutes(30)->subMinutes(10), now()->addMinutes(30)->addMinutes(10)]),
        ];
    }

    /**
     * @param  '24_HOURS'|'30_MINUTES'  $reminderType
     * @return int Số lịch đã gửi nhắc.
     */
    private function sendBatch(string $reminderType, string $markerColumn, array $window): int
    {
        $sent = 0;

        $appointments = Appointment::query()
            ->where('status', 'CONFIRMED')
            ->whereNull($markerColumn)
            ->whereBetween('start_at', $window)
            ->with(['customer.customerProfile', 'service', 'specialistProfile.user'])
            ->get();

        foreach ($appointments as $appointment) {
            // Đánh dấu trước khi gửi để tránh gửi trùng khi chạy song song.
            $appointment->forceFill([$markerColumn => now()])->saveQuietly();

            try {
                $appointment->customer?->notify(new AppointmentReminderNotification($appointment, $reminderType));
                $sent++;
            } catch (\Throwable $exception) {
                Log::error('Không gửi được lời nhắc lịch.', [
                    'appointment_id' => $appointment->id,
                    'reminder_type' => $reminderType,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
