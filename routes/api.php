<?php

use App\Http\Controllers\Api\ConsultationRecordController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/specialists', [BookingController::class, 'specialistsApi'])->name('api.specialists.index');

// Khung giờ còn trống thực sự, đồng bộ trực tiếp với lịch bác sĩ (Yêu cầu 3).
Route::get('/slots', [BookingController::class, 'slotsApi'])->name('api.slots.index');

/*
|--------------------------------------------------------------------------
| Hồ sơ Tư vấn (Consultation Record) — FR-CONSULT-001…003, mục 29 SRS
|--------------------------------------------------------------------------
| Xác thực bằng phiên đăng nhập dùng chung với web (dự án chưa cài Sanctum);
| nhóm middleware `api` đã được bổ sung cookie + session + CSRF.
|
| Phân quyền (BR-049):
|   - Nội dung chuyên môn (`summary`, `follow_up_plan`): CHỈ chuyên viên phụ
|     trách lịch hẹn được đọc/ghi. Lịch hẹn của người khác trả 404.
|   - Khách hàng chỉ đọc được bản tóm lược đã lọc cho khách.
|
| Mọi lần đọc/ghi đều được ghi audit và không bao giờ chứa nội dung (BR-053).
*/

Route::middleware(['auth', 'role:specialist'])->group(function (): void {
    // Đọc / ghi hồ sơ tư vấn của một lịch hẹn.
    Route::get('/appointments/{appointment}/consultation-record', [ConsultationRecordController::class, 'show'])
        ->name('api.consultation-records.show');
    Route::put('/appointments/{appointment}/consultation-record', [ConsultationRecordController::class, 'upsert'])
        ->name('api.consultation-records.upsert');

    // Lịch sử hồ sơ tư vấn của một khách hàng (chỉ các buổi mình phụ trách).
    Route::get('/specialist/customers/{customer}/consultation-records', [ConsultationRecordController::class, 'indexForCustomer'])
        ->name('api.consultation-records.customer');
});

Route::middleware(['auth', 'role:customer'])->group(function (): void {
    // Bản tóm lược hồ sơ tư vấn của chính khách hàng.
    Route::get('/customer/consultation-records', [ConsultationRecordController::class, 'index'])
        ->name('api.customer.consultation-records.index');
});
