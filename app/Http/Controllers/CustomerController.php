<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingSlotUnavailable;
use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Review;
use App\Services\AppointmentWorkflowService;
use App\Support\AppointmentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CustomerController — Phân hệ Khách hàng (SCR-CUS-*)
 *
 * Căn cứ: docs/ThietKeGiaoDien.md §4 (SCR-CUS-01 đến SCR-CUS-07)
 */
class CustomerController extends Controller
{
    /**
     * SCR-CUS-01 — Tổng quan khách hàng (Dashboard)
     */
    public function dashboard(): View
    {
        $userModel = auth()->user();
        $profile = $userModel->customerProfile;
        $user = (object) ['id' => $userModel->id, 'name' => $userModel->name, 'email' => $userModel->email, 'phone' => $userModel->phone, 'code' => $profile?->customer_code ?? 'KH-'.str_pad((string) $userModel->id, 5, '0', STR_PAD_LEFT), 'dob' => $profile?->date_of_birth?->format('d/m/Y'), 'created_at' => $userModel->created_at?->format('d/m/Y')];
        $appointmentQuery = Appointment::query()->where('customer_id', $userModel->id);
        $upcomingAppointments = (clone $appointmentQuery)->holdingSlot()->where('start_at', '>=', now())->with(['service', 'specialistProfile.user'])->orderBy('start_at')->limit(3)->get()->map(fn (Appointment $appointment): object => $this->toViewAppointment($appointment));
        $notifications = $userModel->notifications()->latest()->limit(5)->get()->map(fn ($notification): object => (object) ['id' => $notification->id, 'title' => $notification->data['title'] ?? 'Thông báo', 'content' => $notification->data['message'] ?? '', 'time' => $notification->created_at?->diffForHumans(), 'is_read' => $notification->read_at !== null]);
        $stats = ['total_appointments' => (clone $appointmentQuery)->count(), 'completed_appointments' => (clone $appointmentQuery)->where('status', 'COMPLETED')->count(), 'pending_reviews' => (clone $appointmentQuery)->where('status', 'COMPLETED')->whereDoesntHave('review')->count()];

        return view('customer.dashboard', compact('user', 'upcomingAppointments', 'notifications', 'stats'));
    }

    /**
     * SCR-CUS-04 — Lịch hẹn của tôi
     */
    public function appointments(Request $request): View
    {
        $tab = $request->query('tab', 'upcoming');

        $query = Appointment::query()->where('customer_id', auth()->id())->with(['service', 'specialistProfile.user'])->orderByDesc('start_at');

        // Lọc theo tab
        if ($tab === 'upcoming') {
            $query->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED']);
        } elseif ($tab === 'completed') {
            $query->where('status', 'COMPLETED');
        } elseif ($tab === 'cancelled') {
            $query->whereIn('status', ['CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC', 'NO_SHOW']);
        }

        $appointments = $query->get()->map(fn (Appointment $appointment): object => $this->toViewAppointment($appointment));

        return view('customer.appointments', compact('appointments', 'tab'));
    }

    /**
     * SCR-CUS-05 — Chi tiết lịch hẹn
     */
    public function appointmentDetail(int|string $id): View
    {
        $appointmentModel = Appointment::query()->where('customer_id', auth()->id())->with(['service', 'specialistProfile.user', 'statusHistory', 'refundRequest'])->findOrFail($id);
        $appointment = $this->toViewAppointment($appointmentModel);
        $timeline = $appointmentModel->statusHistory->map(fn ($history): object => (object) ['status' => $history->to_status, 'label' => AppointmentStatus::label($history->to_status), 'time' => $history->created_at?->format('H:i d/m/Y'), 'actor' => $history->actor_role, 'desc' => $history->reason]);

        return view('customer.appointment-detail', compact('appointment', 'timeline'));
    }

    /**
     * SCR-CUS-02 — Hồ sơ cá nhân
     */
    public function profile(): View
    {
        $model = auth()->user()->load('customerProfile');
        $user = (object) ['id' => $model->id, 'name' => $model->name, 'email' => $model->email, 'phone' => $model->phone, 'code' => $model->customerProfile?->customer_code ?? 'KH-'.str_pad((string) $model->id, 5, '0', STR_PAD_LEFT), 'dob' => $model->customerProfile?->date_of_birth?->format('Y-m-d'), 'created_at' => $model->created_at?->format('d/m/Y'), 'email_verified' => $model->email_verified_at !== null];

        return view('customer.profile', compact('user'));
    }

    /**
     * Cập nhật hồ sơ cá nhân
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|regex:/^[0-9+\s\-]{9,15}$/',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
        ], [
            'name.required' => 'Họ và tên không được để trống (2-100 ký tự).',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
        ]);

        $request->user()->update($request->only(['name', 'phone', 'email']));
        CustomerProfile::query()->updateOrCreate(['user_id' => $request->user()->id], ['customer_code' => 'KH-'.str_pad((string) $request->user()->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $request->input('date_of_birth')]);

        return back()->with('success', 'Hồ sơ cá nhân đã được cập nhật thành công.');
    }

    /**
     * Đổi mật khẩu tài khoản
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        if (! Hash::check($request->string('current_password')->toString(), $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.']);
        }
        $request->user()->update(['password' => $request->string('password')->toString()]);

        return back()->with('success', 'Đổi mật khẩu thành công. Mật khẩu mới có hiệu lực ngay lập tức.');
    }

    /**
     * Lịch sử tư vấn & Tóm tắt chuyên viên (Medical History / Consultation Notes)
     */
    public function medicalHistory(): View
    {
        $records = Appointment::query()
            ->where('customer_id', auth()->id())
            ->where('status', 'COMPLETED')
            ->with(['service', 'specialistProfile.user'])
            ->latest('completed_at')
            ->get()
            ->map(fn (Appointment $appointment): object => (object) [
                'id' => $appointment->id,
                'appointment_code' => $appointment->appointment_code,
                'service_name' => $appointment->service->name,
                'specialist_name' => $appointment->specialistProfile->user->name,
                'date' => $appointment->start_at->format('d/m/Y'),
                'time_slot' => $appointment->start_at->format('H:i').' - '.$appointment->end_at->format('H:i'),
                'summary' => 'Hồ sơ chuyên môn được bảo mật và chỉ chuyên viên phụ trách được truy cập.',
                'recommendation' => 'Vui lòng đặt lịch theo dõi hoặc liên hệ phòng khám nếu cần hỗ trợ tiếp.',
                'homework' => null,
            ]);

        return view('customer.medical-history', compact('records'));
    }

    /**
     * SCR-CUS-07 — Đánh giá của tôi / Form viết nhận xét
     */
    public function review(Request $request): View
    {
        $appointmentId = $request->query('appointment_id');
        $pendingReviews = Appointment::query()->where('customer_id', auth()->id())->where('status', 'COMPLETED')->whereDoesntHave('review')->where('completed_at', '>=', now()->subDays(30))->with(['service', 'specialistProfile.user'])->get()->map(fn (Appointment $appointment): object => (object) ['id' => $appointment->id, 'code' => $appointment->appointment_code, 'service_name' => $appointment->service->name, 'specialist_name' => $appointment->specialistProfile->user->name, 'specialist_title' => $appointment->specialistProfile->title, 'date' => $appointment->start_at->format('d/m/Y'), 'time_slot' => $appointment->start_at->format('H:i').' - '.$appointment->end_at->format('H:i'), 'days_left' => max(0, 30 - (int) $appointment->completed_at->diffInDays(now()))]);
        $completedReviews = Review::query()->where('customer_id', auth()->id())->with(['appointment.service', 'specialistProfile.user'])->latest()->get()->map(fn (Review $review): object => (object) ['id' => $review->appointment_id, 'code' => $review->appointment->appointment_code, 'service_name' => $review->appointment->service->name, 'specialist_name' => $review->specialistProfile->user->name, 'date' => $review->appointment->start_at->format('d/m/Y'), 'rating' => $review->rating, 'comment' => $review->comment, 'created_at' => $review->created_at->format('d/m/Y')]);

        return view('customer.review', compact('pendingReviews', 'completedReviews', 'appointmentId'));
    }

    /**
     * Gửi đánh giá cho chuyên viên
     */
    public function submitReview(Request $request): RedirectResponse
    {
        $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá (1-5 sao).',
            'rating.between' => 'Số sao đánh giá phải từ 1 đến 5.',
            'comment.max' => 'Nhận xét không được vượt quá 1000 ký tự.',
        ]);

        $appointment = Appointment::query()->where('customer_id', $request->user()->id)->where('status', 'COMPLETED')->where('completed_at', '>=', now()->subDays(30))->findOrFail($request->integer('appointment_id'));
        abort_if($appointment->review()->exists(), 409, 'Lịch hẹn này đã được đánh giá.');
        Review::query()->create(['appointment_id' => $appointment->id, 'customer_id' => $request->user()->id, 'specialist_profile_id' => $appointment->specialist_profile_id, 'rating' => $request->integer('rating'), 'comment' => $request->input('comment'), 'status' => 'VISIBLE']);

        return back()->with('success', 'Cảm ơn bạn đã gửi đánh giá. Nhận xét của bạn sẽ được hiển thị ẩn danh để bảo vệ quyền riêng tư.');
    }

    /**
     * Hủy lịch hẹn
     */
    public function cancelAppointment(Request $request, int|string $id, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500'], 'bank_name' => ['nullable', 'string', 'max:100'], 'bank_account' => ['nullable', 'string', 'max:50'], 'bank_owner' => ['nullable', 'string', 'max:100']]);
        try {
            $workflow->cancelByCustomer(Appointment::query()->findOrFail($id), $request->user(), $data['reason'] ?? null, ['bank_name' => $data['bank_name'] ?? null, 'bank_account' => $data['bank_account'] ?? null, 'account_holder' => $data['bank_owner'] ?? null]);
        } catch (HttpException $exception) {
            return redirect()->route('customer.appointments.detail', $id)->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Lịch hẹn đã được hủy thành công. Hệ thống đã cập nhật trạng thái hủy.');
    }

    /**
     * Yêu cầu đổi lịch
     */
    public function rescheduleAppointment(Request $request, int|string $id, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['new_date' => ['required', 'date_format:Y-m-d'], 'new_time' => ['required', 'regex:/^([01][0-9]|2[0-3]):[0-5][0-9](\s*-\s*([01][0-9]|2[0-3]):[0-5][0-9])?$/'], 'reason' => ['nullable', 'string', 'max:500']]);
        $startTime = trim(explode('-', $data['new_time'], 2)[0]);
        try {
            $workflow->requestReschedule(Appointment::query()->findOrFail($id), $request->user(), CarbonImmutable::createFromFormat('Y-m-d H:i', $data['new_date'].' '.$startTime), $data['reason'] ?? null);
        } catch (BookingSlotUnavailable|HttpException $exception) {
            return redirect()->route('customer.appointments.detail', $id)->with('error', $this->rescheduleErrorMessage($exception, Appointment::query()->find($id)));
        }

        return back()->with('success', 'Yêu cầu đổi lịch hẹn đã được gửi tới phòng khám để xử lý.');
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------
    private function getSampleAppointments(): Collection
    {
        return collect([
            (object) [
                'id' => 201,
                'code' => 'MG24101',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'specialist_title' => 'Chuyên viên Tâm lý học lâm sàng',
                'date' => date('Y-m-d', strtotime('+2 days')),
                'time_slot' => '09:30 - 10:30',
                'status' => 'CONFIRMED',
                'payment_status' => 'PAID',
                'payment_method' => 'BANK_QR',
                'amount' => 500000,
                'type' => 'ONLINE',
                'customer_note' => 'Cảm thấy mất ngủ kéo dài và thường xuyên hồi hộp khi bắt đầu ngày mới.',
                'location' => 'Phòng họp trực tuyến bảo mật',
                'meeting_url' => 'https://meet.mindguide.vn/room/mg-24101',
                'can_reschedule' => true,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 202,
                'code' => 'MG24102',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => date('Y-m-d', strtotime('+5 days')),
                'time_slot' => '15:00 - 16:30',
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'payment_method' => 'BANK_QR',
                'amount' => 750000,
                'type' => 'OFFLINE',
                'customer_note' => 'Gặp khó khăn trong việc cân bằng công việc và cuộc sống hôn nhân.',
                'location' => 'Phòng 201, Tầng 2 - Cơ sở Quận 1, TP.HCM',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 203,
                'code' => 'MG24095',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => '05/10/2026',
                'time_slot' => '15:00 - 16:30',
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => 'BANK_QR',
                'amount' => 750000,
                'type' => 'OFFLINE',
                'customer_note' => null,
                'location' => 'Phòng 201, Tầng 2 - Cơ sở Quận 1',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 204,
                'code' => 'MG24089',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'specialist_title' => 'Chuyên viên Tâm lý học lâm sàng',
                'date' => '28/09/2026',
                'time_slot' => '09:00 - 10:00',
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => 'CASH',
                'amount' => 500000,
                'type' => 'OFFLINE',
                'customer_note' => 'Tham vấn lần đầu tiên.',
                'location' => 'Phòng 102, Tầng 1 - Cơ sở Quận 1',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 205,
                'code' => 'MG24070',
                'service_name' => 'Tư vấn hôn nhân & Gia đình',
                'specialist_name' => 'ThS. Đỗ Thúy Hằng',
                'specialist_title' => 'Chuyên viên Trị liệu Gia đình',
                'date' => '15/09/2026',
                'time_slot' => '14:00 - 15:30',
                'status' => 'CANCELLED_BY_CUSTOMER',
                'payment_status' => 'REFUNDED',
                'payment_method' => 'BANK_QR',
                'amount' => 800000,
                'type' => 'ONLINE',
                'customer_note' => 'Bận công tác đột xuất.',
                'location' => 'Phòng họp trực tuyến',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
        ]);
    }

    private function toViewAppointment(Appointment $appointment): object
    {
        return (object) [
            'id' => $appointment->id,
            'code' => $appointment->appointment_code,
            'service_name' => $appointment->service->name,
            'specialist_name' => $appointment->specialistProfile->user->name,
            'specialist_title' => $appointment->specialistProfile->title,
            'date' => $appointment->start_at->format('Y-m-d'),
            'time_slot' => $appointment->start_at->format('H:i').' - '.$appointment->end_at->format('H:i'),
            'status' => $appointment->status,
            'payment_status' => $appointment->payment_status,
            'payment_method' => $appointment->payment_method,
            'amount' => (int) $appointment->price,
            'type' => $appointment->source === 'ONLINE' ? 'ONLINE' : 'OFFLINE',
            'customer_note' => $appointment->customer_note,
            'location' => 'Phòng khám Mind Guide',
            'meeting_url' => null,
            'can_reschedule' => $appointment->status === 'CONFIRMED' && $appointment->start_at->gte(now()->addHours(24)) && $appointment->reschedule_count < 2,
            'reschedule_count' => $appointment->reschedule_count,
            'refund_amount' => (int) ($appointment->refund_amount ?? 0),
            'refund_status' => $appointment->refund_status,
        ];
    }

    private function rescheduleErrorMessage(\Throwable $exception, ?Appointment $appointment): string
    {
        if ($appointment?->status === 'PENDING') {
            return 'Lịch đang chờ thanh toán hoặc xác nhận. Bạn chỉ có thể đổi lịch sau khi lịch chuyển sang Đã xác nhận.';
        }
        if ($appointment?->status !== 'CONFIRMED') {
            return 'Lịch hẹn ở trạng thái hiện tại không thể yêu cầu đổi lịch.';
        }
        if ($appointment->start_at->lt(now()->addHours(24))) {
            return 'Chỉ có thể yêu cầu đổi lịch trước giờ hẹn ít nhất 24 giờ.';
        }
        if ($appointment->reschedule_count >= 2) {
            return 'Lịch hẹn đã đạt giới hạn đổi lịch tối đa 2 lần.';
        }

        return $exception->getMessage() ?: 'Không thể đổi sang khung giờ đã chọn.';
    }
}
