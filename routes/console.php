<?php

use App\Models\Appointment;
use App\Services\AppointmentReminderService;
use App\Services\AppointmentWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Hết hạn lịch chờ thanh toán và gửi nhắc lịch tự động (Yêu cầu 2).
 *
 * Nhắc lần 1 trước 24 giờ, lần 2 trước 30 phút — mỗi mốc gửi qua cả email
 * và thông báo trong hệ thống, kèm thông tin khách hàng đầy đủ.
 */
Artisan::command('appointments:maintain', function (AppointmentWorkflowService $workflow, AppointmentReminderService $reminders) {
    $expired = $workflow->expirePending();
    $sent = $reminders->sendDue();

    $this->info(sprintf(
        'Đã hết hạn %d lịch chờ thanh toán, gửi %d nhắc 24 giờ và %d nhắc 30 phút.',
        $expired,
        $sent['24_HOURS'],
        $sent['30_MINUTES'],
    ));
})->purpose('Hết hạn lịch chờ thanh toán và gửi nhắc lịch 24 giờ / 30 phút trước giờ hẹn');

/**
 * Nhắc lịch chạy mỗi 5 phút để độ trễ thấp nhất có thể với mốc 30 phút.
 */
Schedule::command('appointments:maintain')->everyFiveMinutes()->withoutOverlapping();

/**
 * Cảnh báo sớm khi lịch hẹn bị ảnh hưởng bởi ngày nghỉ / chặn giờ.
 */
Artisan::command('appointments:flag-blocked', function () {
    $flagged = Appointment::query()
        ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
        ->where('needs_attention', false)
        ->whereDate('start_at', '<=', now()->addDays(7)->toDateString())
        ->whereHas('specialistProfile.scheduleExceptions', function ($query) {
            $query->whereDate('start_date', '<=', now()->addDays(7)->toDateString())
                ->whereDate('end_date', '>=', now()->toDateString());
        })
        ->update(['needs_attention' => true, 'attention_reason' => 'Chuyên viên nghỉ hoặc bị chặn giờ', 'attention_flagged_at' => now()]);

    $this->info("Đã gắn cờ {$flagged} lịch bị ảnh hưởng bởi lịch nghỉ sắp tới.");
})->purpose('Gắn cờ cần xử lý cho lịch hẹn trùng ngày nghỉ sắp tới');

Schedule::command('appointments:flag-blocked')->dailyAt('06:00');
