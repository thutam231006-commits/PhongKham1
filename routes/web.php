<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SepayWebhookController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\StaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Mind Guide (MHCA)
|--------------------------------------------------------------------------
| Tổ chức theo nhóm vai trò:
|   - Public (Guest / mọi người): /, /services, /specialists, /booking, /about, /contact, /faq
|   - Auth   (SCR-PUB-06 -> 10)
|   - Customer · Specialist · Staff · Admin (phân quyền middleware)
|--------------------------------------------------------------------------
*/

// ============================================================
// PUBLIC — Không yêu cầu xác thực (SCR-PUB-*)
// ============================================================

// SCR-PUB-01 — Trang chủ
Route::get('/', [HomeController::class, 'index'])->name('home');
// SCR-PUB-02 — Danh sách dịch vụ (/services & /dich-vu)
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/dich-vu', [ServiceController::class, 'index']);

// SCR-PUB-03 — Chi tiết dịch vụ
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/dich-vu/{service}', [ServiceController::class, 'show']);

// SCR-PUB-04 — Danh sách chuyên viên (/specialists & /chuyen-vien)
Route::get('/specialists', [SpecialistController::class, 'index'])->name('specialists.index');
Route::get('/chuyen-vien', [SpecialistController::class, 'index']);

// SCR-PUB-05 — Chi tiết chuyên viên & đánh giá
Route::get('/specialists/{specialist}', [SpecialistController::class, 'show'])->name('specialists.show');
Route::get('/chuyen-vien/{specialist}', [SpecialistController::class, 'show']);

// SCR-CUS-03 — Đặt lịch tư vấn (/booking & /dat-lich)
Route::get('/booking', [BookingController::class, 'index'])->name('appointments.book');
Route::get('/booking/select-specialist', [BookingController::class, 'selectSpecialist'])->name('booking.select-specialist');
Route::get('/booking/select-slot', [BookingController::class, 'selectSlot'])->name('booking.select-slot');
Route::get('/booking/note', [BookingController::class, 'note'])->name('booking.note');
Route::get('/booking/confirm', [BookingController::class, 'confirm'])->name('booking.confirm');
Route::post('/booking/step/service', [BookingController::class, 'storeServiceStep'])->name('booking.step.service');
Route::post('/booking/step/specialist', [BookingController::class, 'storeSpecialistStep'])->name('booking.step.specialist');
Route::post('/booking/step/slot', [BookingController::class, 'storeSlotStep'])->name('booking.step.slot');
Route::post('/booking/step/note', [BookingController::class, 'storeNoteStep'])->name('booking.step.note');
Route::get('/dat-lich', [BookingController::class, 'index']);

// Trang thông tin tĩnh & Hỗ trợ công khai
Route::get('/gioi-thieu', [PageController::class, 'about'])->name('about');
Route::get('/about', [PageController::class, 'about']);

Route::get('/lien-he', [PageController::class, 'contact'])->name('contact');
Route::get('/contact', [PageController::class, 'contact']);
Route::post('/lien-he', [PageController::class, 'sendContact'])->name('contact.send');

Route::get('/hoi-dap', [PageController::class, 'faq'])->name('faq');
Route::get('/faq', [PageController::class, 'faq']);

// Pháp lý & Điều khoản
Route::get('/chinh-sach-quyen-rieng-tu', [PageController::class, 'privacy'])->name('privacy');
Route::get('/privacy', [PageController::class, 'privacy']);
Route::get('/dieu-khoan-su-dung', [PageController::class, 'terms'])->name('terms');
Route::get('/terms', [PageController::class, 'terms']);

Route::post('/webhooks/sepay', [SepayWebhookController::class, 'sepay'])->middleware('throttle:60,1')->name('webhooks.sepay');

// ------------------------------------------------------------
// XÁC THỰC CÔNG KHAI (SCR-PUB-06 -> SCR-PUB-10)
// ------------------------------------------------------------

// SCR-PUB-06 — Đăng nhập
Route::get('/dang-nhap', [AuthController::class, 'loginForm'])->name('login');
Route::post('/dang-nhap', [AuthController::class, 'login'])->middleware('throttle:authentication')->name('login.post');

// SCR-PUB-07 — Đăng ký
Route::get('/dang-ky', [AuthController::class, 'registerForm'])->name('register');
Route::post('/dang-ky', [AuthController::class, 'register'])->middleware('throttle:authentication')->name('register.post');

// SCR-PUB-08 — Quên mật khẩu
Route::get('/quen-mat-khau', [AuthController::class, 'forgotForm'])->name('password.request');
Route::post('/quen-mat-khau', [AuthController::class, 'sendResetLink'])->middleware('throttle:authentication')->name('password.email');

// SCR-PUB-09 — Đặt lại mật khẩu
Route::get('/dat-lai-mat-khau/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])->name('password.update');

// SCR-PUB-10 — Xác thực email
Route::get('/xac-thuc-email', [AuthController::class, 'verifyNotice'])->name('verification.notice');
Route::get('/xac-thuc-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
Route::post('/xac-thuc-email/gui-lai', [AuthController::class, 'resendVerification'])->middleware('throttle:3,60')->name('verification.resend');

// ============================================================
// AUTH — Yêu cầu đăng nhập (middleware 'auth')
// ============================================================

Route::middleware('auth')->group(function () {

    // SCR-CUS-03 — Tạo lịch hẹn tiền mặt trong MVP
    Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:booking')->name('appointments.store');
    Route::post('/booking/confirm', [BookingController::class, 'confirmBooking'])->middleware('throttle:booking')->name('booking.confirm.store');
    Route::get('/booking/payment/{appointment}', [BookingController::class, 'payment'])->name('booking.payment.show');
    Route::get('/booking/payment/{appointment}/status', [BookingController::class, 'paymentStatus'])->middleware('throttle:60,1')->name('booking.payment.status');

    // Đăng xuất
    Route::match(['get', 'post'], '/dang-xuat', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất tài khoản thành công.');
    })->name('logout');

    // SCR-COM-01 — Thông báo
    Route::get('/thong-bao', [AccountController::class, 'notifications'])->name('notifications.index');
    Route::post('/thong-bao/doc-tat-ca', [AccountController::class, 'readAllNotifications'])->name('notifications.read-all');
    Route::get('/thong-bao/{id}', [AccountController::class, 'readNotification'])->name('notifications.read');

    // SCR-COM-02 — Đổi mật khẩu
    Route::get('/doi-mat-khau', [AccountController::class, 'changePassword'])->name('password.change');
    Route::put('/doi-mat-khau', [AccountController::class, 'updatePassword'])->name('password.change.update');

    // ----------------------------------------------------------
    // CUSTOMER (SCR-CUS-*)
    // ----------------------------------------------------------
    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        // SCR-CUS-01 — Tổng quan (Dashboard)
        Route::get('/', [CustomerController::class, 'dashboard']);
        Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');

        // SCR-CUS-02 — Hồ sơ cá nhân
        Route::get('/profile', [CustomerController::class, 'profile'])->name('profile');
        Route::post('/profile', [CustomerController::class, 'updateProfile'])->name('profile.update');
        Route::post('/change-password', [CustomerController::class, 'changePassword'])->name('password.change');

        // SCR-CUS-04 — Lịch hẹn của tôi
        Route::get('/appointments', [CustomerController::class, 'appointments'])->name('appointments');

        // SCR-CUS-05, SCR-CUS-06 — Chi tiết lịch hẹn & Thao tác Hủy / Đổi lịch
        Route::get('/appointments/{id}', [CustomerController::class, 'appointmentDetail'])->name('appointments.detail');
        Route::post('/appointments/{id}/cancel', [CustomerController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::post('/appointments/{id}/reschedule', [CustomerController::class, 'rescheduleAppointment'])->name('appointments.reschedule');

        // Lịch sử tư vấn & ghi chú tóm tắt chuyên viên (§1.7 bảo mật)
        Route::get('/medical-history', [CustomerController::class, 'medicalHistory'])->name('medical-history');

        // SCR-CUS-07 — Đánh giá chuyên viên
        Route::get('/review', [CustomerController::class, 'review'])->name('review');
        Route::post('/review', [CustomerController::class, 'submitReview'])->name('review.submit');
    });

    // Alias tiếng Việt /khach-hang
    Route::prefix('khach-hang')->group(function () {
        Route::get('/', fn () => redirect()->route('customer.dashboard'));
        Route::get('/tong-quan', fn () => redirect()->route('customer.dashboard'));
        Route::get('/ho-so', fn () => redirect()->route('customer.profile'));
        Route::get('/lich-hen', fn () => redirect()->route('customer.appointments'));
        Route::get('/lich-hen/{id}', fn ($id) => redirect()->route('customer.appointments.detail', $id));
        Route::get('/lich-su-tu-van', fn () => redirect()->route('customer.medical-history'));
        Route::get('/danh-gia', fn () => redirect()->route('customer.review'));
    });

    // ----------------------------------------------------------
    // SPECIALIST (SCR-SPE-*)
    // ----------------------------------------------------------
    Route::middleware('role:specialist')->prefix('specialist')->name('specialist.')->group(function () {
        Route::get('/dashboard', [SpecialistController::class, 'dashboard'])->name('dashboard');
        Route::get('/schedule', [SpecialistController::class, 'schedule'])->name('schedule');
        Route::get('/leaves', [SpecialistController::class, 'schedule'])->name('leaves');
        Route::post('/schedule', [SpecialistController::class, 'storeSchedule'])->name('schedule.store');
        Route::post('/leaves', [SpecialistController::class, 'storeScheduleException'])->name('leaves.store');
        Route::put('/schedule/{workingSchedule}', [SpecialistController::class, 'updateSchedule'])->name('schedule.update');
        Route::get('/appointments', [SpecialistController::class, 'appointments'])->name('appointments');
        Route::get('/appointments/{id}', [SpecialistController::class, 'appointmentDetail'])->name('appointments.detail');
        Route::post('/appointments/{id}/start', [SpecialistController::class, 'startAppointment'])->name('appointments.start');
        Route::post('/appointments/{id}/complete', [SpecialistController::class, 'completeAppointment'])->name('appointments.complete');
        Route::post('/appointments/{id}/no-show', [SpecialistController::class, 'noShowAppointment'])->name('appointments.no-show');
        Route::get('/appointments/{id}/consultation-note', [SpecialistController::class, 'consultationNote'])->name('consultation-note');
        Route::post('/appointments/{id}/consultation-note', [SpecialistController::class, 'storeConsultationNote'])->name('consultation-note.store');
        Route::get('/profile', [SpecialistController::class, 'profile'])->name('profile');
        Route::put('/profile', [SpecialistController::class, 'updateProfile'])->name('profile.update');
    });

    // ----------------------------------------------------------
    // STAFF (SCR-STF-*)
    // ----------------------------------------------------------
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::get('/checkin', [StaffController::class, 'checkin'])->name('checkin');
        Route::post('/checkin/{id}', [StaffController::class, 'markCheckin'])->name('checkin.store');
        Route::get('/appointments', [StaffController::class, 'appointmentList'])->name('appointments');
        Route::get('/appointments/pending', [StaffController::class, 'appointments'])->name('appointments.pending');
        Route::post('/appointments/{appointment}/confirm', [StaffController::class, 'confirmAppointment'])->name('appointments.confirm');
        Route::post('/appointments/{appointment}/reassign', [StaffController::class, 'reassignAppointment'])->name('appointments.reassign');
        Route::post('/appointments/{appointment}/cancel', [StaffController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::post('/reschedule-requests/{rescheduleRequest}', [StaffController::class, 'resolveReschedule'])->name('reschedule-requests.resolve');
        Route::get('/customers', [StaffController::class, 'customers'])->name('customers');
        Route::post('/customers', [StaffController::class, 'storeCustomer'])->name('customers.store');
        Route::put('/customers/{user}', [StaffController::class, 'updateCustomer'])->name('customers.update');
        Route::get('/booking-onsite', [StaffController::class, 'bookingOnsite'])->name('appointments.book');
        Route::post('/booking-onsite', [StaffController::class, 'storeBookingOnsite'])->name('appointments.store');
        Route::get('/payments', [StaffController::class, 'payments'])->name('payments');
        Route::post('/payments/{id}/collect', [StaffController::class, 'collectPayment'])->name('payments.collect');
        Route::post('/refunds/{refundRequest}', [StaffController::class, 'markRefunded'])->name('refunds.complete');
    });

    // ----------------------------------------------------------
    // ADMIN (SCR-ADM-*)
    // ----------------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::get('/specialists', [AdminController::class, 'specialists'])->name('specialists');
        Route::post('/specialists', [AdminController::class, 'storeSpecialist'])->name('specialists.store');
        Route::put('/specialists/{specialistProfile}', [AdminController::class, 'updateSpecialist'])->name('specialists.update');
        Route::post('/specialists/{specialistProfile}/toggle', [AdminController::class, 'toggleSpecialist'])->name('specialists.toggle');
        Route::get('/services', [AdminController::class, 'services'])->name('services');
        Route::post('/services', [AdminController::class, 'storeService'])->name('services.store');
        Route::put('/services/{service}', [AdminController::class, 'updateService'])->name('services.update');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [AdminController::class, 'exportReport'])->name('reports.export');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
        Route::get('/schedule', [AdminController::class, 'schedule'])->name('schedule');
        Route::post('/schedule', [AdminController::class, 'storeScheduleException'])->name('schedule.store');
        Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
        Route::put('/reviews/{review}', [AdminController::class, 'moderateReview'])->name('reviews.update');
        Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [AdminController::class, 'sendNotification'])->name('notifications.store');
        Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{serviceCategory}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::get('/roles', [AdminController::class, 'roles'])->name('roles');
        Route::put('/roles/{role}', [AdminController::class, 'updateRole'])->name('roles.update');
        Route::get('/appointments', [AdminController::class, 'appointments'])->name('appointments');
        Route::post('/appointments/{appointment}/cancel', [AdminController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::post('/refunds/{refundRequest}', [AdminController::class, 'markRefunded'])->name('refunds.complete');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    });

});
