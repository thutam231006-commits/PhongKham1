<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectPaymentRequest;
use App\Models\Appointment;
use App\Models\ConsultationRoom;
use App\Models\CustomerProfile;
use App\Models\RefundRequest;
use App\Models\RescheduleRequest;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\AppointmentBookingService;
use App\Services\AppointmentTrafficService;
use App\Services\AppointmentWorkflowService;
use App\Services\AttentionService;
use App\Services\AuditService;
use App\Services\EmailVerificationService;
use App\Services\MailHealthService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\RoomAllocationService;
use App\Services\SlotAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Trạng thái lịch hẹn mà lễ tân được phép xác nhận thu tiền mặt.
     *
     * Bao gồm cả thu trước giờ tư vấn (PENDING/CONFIRMED), thu trong buổi tư vấn
     * (IN_PROGRESS) và thu sau buổi tư vấn (COMPLETED).
     *
     * @var list<string>
     */
    private const CASH_COLLECTIBLE_STATUSES = ['PENDING', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED'];

    public function __construct(private readonly SlotAvailabilityService $slots) {}

    public function dashboard(Request $request, NotificationService $notifications, SlotAvailabilityService $slots, AppointmentTrafficService $traffic, MailHealthService $mailHealth): View
    {
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $today = Appointment::query()->whereDate('start_at', $date)->with(['customer', 'specialistProfile.user', 'service'])->orderBy('start_at')->get();
        $cashToCollect = Appointment::query()->whereIn('status', self::CASH_COLLECTIBLE_STATUSES)->where('payment_status', 'UNPAID')->where('payment_method', 'CASH')->count();
        $needsAttention = Appointment::query()->where('needs_attention', true)->count();
        $attentionAppointments = Appointment::query()->where('needs_attention', true)->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])->with(['customer', 'service', 'specialistProfile.user'])->orderBy('start_at')->limit(6)->get();
        $pendingAppointments = Appointment::query()->where('status', 'PENDING')->with(['customer', 'service', 'specialistProfile.user'])->oldest('created_at')->get();
        $pending = $pendingAppointments->count();
        $staffRecipients = User::query()->whereIn('role', ['staff', 'admin'])->where('status', 'ACTIVE')->get();
        foreach ($pendingAppointments as $appointment) {
            foreach ($staffRecipients as $recipient) {
                $alreadyNotified = $recipient->notifications()->where('data->appointment_id', $appointment->id)->where('data->type', 'appointment.attention')->exists();
                if (! $alreadyNotified) {
                    $notifications->send($recipient, 'Lịch mới chờ xác nhận', $appointment->customer->name.' vừa đặt lịch '.$appointment->service->name.' lúc '.$appointment->start_at->format('d/m/Y H:i').'.', 'appointment.attention', route('staff.appointments'), $appointment);
                }
            }
        }
        $pendingCheckins = Appointment::query()->whereDate('start_at', $date)->where('status', 'CONFIRMED')->whereNull('checked_in_at')->where('consultation_type', 'OFFLINE')->count();
        $rescheduleRequests = RescheduleRequest::query()->where('status', 'PENDING')->count();
        $refundsPending = RefundRequest::query()->where('status', 'PENDING')->count();
        $newCustomers = User::query()->where('role', 'customer')->where('created_at', '>=', now()->subDays(7))->count();
        $unreadNotifications = $request->user()->unreadNotifications()->count();
        $notifications = $request->user()->notifications()->latest()->limit(8)->get();
        $urgentAppointments = Appointment::query()->whereIn('status', ['PENDING', 'RESCHEDULE_REQUESTED'])->with(['customer', 'service', 'specialistProfile.user'])->orderBy('created_at')->limit(8)->get();

        // Yêu cầu 1: tải lịch theo từng chuyên viên — số bệnh nhân và số slot trống.
        $specialistLoad = SpecialistProfile::query()
            ->where('status', 'ACTIVE')
            ->with('user')
            ->orderBy('display_order')
            ->get()
            ->map(fn (SpecialistProfile $specialist): object => (object) [
                'id' => $specialist->id,
                'name' => $specialist->user->name,
                ...$slots->dailyLoadFor($specialist, $date),
            ]);

        // Lưu lượng theo khung giờ trong ngày để nhân viên nắm giờ cao điểm.
        $hourlyTraffic = $traffic->byHourOfDay($date);
        $peakHour = $hourlyTraffic->sortByDesc('total')->first();

        $cashAppointments = Appointment::query()->whereIn('status', self::CASH_COLLECTIBLE_STATUSES)->where('payment_status', 'UNPAID')->where('payment_method', 'CASH')->with(['customer', 'service'])->orderBy('start_at')->limit(6)->get();

        $mailHealthStatus = $mailHealth->inspectConfiguration();

        return view('staff.dashboard', compact('date', 'today', 'cashToCollect', 'needsAttention', 'attentionAppointments', 'cashAppointments', 'pending', 'pendingCheckins', 'rescheduleRequests', 'refundsPending', 'newCustomers', 'unreadNotifications', 'notifications', 'urgentAppointments', 'specialistLoad', 'hourlyTraffic', 'peakHour', 'mailHealthStatus'));
    }

    public function customerSearch(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($data['q']);
        $appointments = Appointment::query()
            ->whereDate('start_at', today())
            ->whereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))
            ->with(['customer.customerProfile', 'specialistProfile.user', 'service'])
            ->orderBy('start_at')
            ->limit(10)
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'appointment_code' => $appointment->appointment_code,
                'customer' => ['name' => $appointment->customer->name, 'email' => $appointment->customer->email, 'phone' => $appointment->customer->phone, 'code' => $appointment->customer->customerProfile?->customer_code],
                'service' => $appointment->service->name,
                'specialist' => $appointment->specialistProfile->user->name,
                'time' => $appointment->start_at->format('H:i').' - '.$appointment->end_at->format('H:i'),
                'status' => $appointment->status,
                'checked_in' => $appointment->checked_in_at !== null,
                'payment_status' => $appointment->payment_status,
                'payment_method' => $appointment->payment_method,
            ]);

        return response()->json(['success' => true, 'data' => $appointments, 'error' => null]);
    }

    public function checkin(Request $request): View
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $date = $data['date'] ?? today()->toDateString();
        $query = Appointment::query()
            ->whereDate('start_at', $date)
            ->with(['customer.customerProfile', 'specialistProfile.user', 'service'])
            ->orderBy('start_at');

        foreach (['name', 'email', 'phone'] as $field) {
            if ($request->filled($field)) {
                $query->whereHas('customer', fn ($customer) => $customer->where($field, 'like', '%'.trim($data[$field]).'%'));
            }
        }
        if ($request->filled('q')) {
            $term = trim($data['q']);
            $query->where(function ($builder) use ($term): void {
                $builder->whereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))
                    ->orWhereHas('customer.customerProfile', fn ($profile) => $profile->where('customer_code', 'like', "%{$term}%"));
            });
        }
        $appointments = $query->paginate(20)->withQueryString();

        return view('staff.checkin', compact('appointments'));
    }

    public function appointmentList(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'NO_SHOW', 'CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC'])],
            'specialist_id' => ['nullable', 'integer', 'exists:specialist_profiles,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'payment_status' => ['nullable', Rule::in(['UNPAID', 'PAID', 'REFUND_PENDING', 'REFUNDED'])],
            'payment_method' => ['nullable', Rule::in(['CASH', 'BANK_QR'])],
            'attention' => ['nullable', 'boolean'],
            'unpaid_cash' => ['nullable', 'boolean'],
        ]);
        $query = Appointment::query()->with(['customer', 'specialistProfile.user', 'service', 'consultationRoom'])->orderByDesc('start_at');
        $query->when($data['status'] ?? null, fn ($builder, string $status) => $builder->where('status', $status));
        $query->when($data['specialist_id'] ?? null, fn ($builder, int $specialistId) => $builder->where('specialist_profile_id', $specialistId));
        $query->when($data['service_id'] ?? null, fn ($builder, int $serviceId) => $builder->where('service_id', $serviceId));
        $query->when($data['date_from'] ?? null, fn ($builder, string $date) => $builder->whereDate('start_at', '>=', $date));
        $query->when($data['date_to'] ?? null, fn ($builder, string $date) => $builder->whereDate('start_at', '<=', $date));
        $query->when($data['payment_status'] ?? null, fn ($builder, string $status) => $builder->where('payment_status', $status));
        $query->when($data['payment_method'] ?? null, fn ($builder, string $method) => $builder->where('payment_method', $method));
        $query->when($request->boolean('attention'), fn ($builder) => $builder->where('needs_attention', true));
        $query->when($request->boolean('unpaid_cash'), fn ($builder) => $builder->where('status', 'COMPLETED')->where('payment_status', 'UNPAID')->where('payment_method', 'CASH'));
        $query->when($data['q'] ?? null, function ($builder, string $term): void {
            $builder->where(function ($nested) use ($term): void {
                $nested->where('appointment_code', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
            });
        });
        $appointments = $query->paginate(20)->withQueryString();
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();
        $services = Service::query()->active()->orderBy('display_order')->get();

        // Thẻ số liệu tổng quan — bỏ qua bộ lọc đang áp dụng để phản ánh toàn bộ tình hình.
        $summaryQuery = Appointment::query();
        $summary = [
            'pending' => (clone $summaryQuery)->where('status', 'PENDING')->count(),
            'today' => (clone $summaryQuery)->whereDate('start_at', today())->count(),
            'attention' => (clone $summaryQuery)->where('needs_attention', true)->count(),
            'unpaidCash' => (clone $summaryQuery)->where('status', 'COMPLETED')->where('payment_status', 'UNPAID')->where('payment_method', 'CASH')->count(),
            'total' => (clone $summaryQuery)->count(),
        ];

        return view('staff.appointment-list', compact('appointments', 'specialists', 'services', 'data', 'summary'));
    }

    public function appointments(Request $request): View
    {
        $data = $request->validate([
            'view' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'NO_SHOW', 'CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC'])],
            'specialist_id' => ['nullable', 'integer', 'exists:specialist_profiles,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'attention' => ['nullable', 'boolean'],
        ]);
        $view = $data['view'] ?? ($request->routeIs('staff.appointments.pending') ? 'month' : 'week');
        $selectedStatus = $data['status'] ?? ($request->routeIs('staff.appointments.pending') ? 'PENDING' : null);
        $selectedDate = CarbonImmutable::parse($data['date'] ?? today()->toDateString());
        $rangeStart = match ($view) {
            'day' => $selectedDate->startOfDay(),
            'month' => $selectedDate->startOfMonth()->startOfWeek(),
            default => $selectedDate->startOfWeek(),
        };
        $rangeEnd = match ($view) {
            'day' => $selectedDate->endOfDay(),
            'month' => $selectedDate->endOfMonth()->endOfWeek(),
            default => $selectedDate->endOfWeek(),
        };
        $query = Appointment::query()->with(['customer', 'specialistProfile.user', 'service', 'consultationRoom'])->whereBetween('start_at', [$rangeStart, $rangeEnd])->orderBy('start_at');
        $query->when($selectedStatus, fn ($builder, string $status) => $builder->where('status', $status));
        $query->when($data['specialist_id'] ?? null, fn ($builder, int $specialistId) => $builder->where('specialist_profile_id', $specialistId));
        $query->when($data['service_id'] ?? null, fn ($builder, int $serviceId) => $builder->where('service_id', $serviceId));
        $query->when($request->boolean('attention'), fn ($builder) => $builder->where('needs_attention', true));
        $appointments = $query->get();
        $calendarDays = [];
        for ($day = $rangeStart->startOfDay(); $day->lte($rangeEnd->startOfDay()); $day = $day->addDay()) {
            $calendarDays[] = $day;
        }
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();
        $services = Service::query()->active()->orderBy('display_order')->get();
        $rescheduleRequests = RescheduleRequest::query()->where('status', 'PENDING')->with(['appointment.customer', 'appointment.service', 'appointment.specialistProfile.user'])->latest()->get();

        return view('staff.appointments', compact('appointments', 'rescheduleRequests', 'specialists', 'services', 'calendarDays', 'selectedDate', 'view', 'rangeStart', 'rangeEnd', 'data'));
    }

    public function markCheckin(int $id, NotificationService $notifications): RedirectResponse
    {
        $appointment = Appointment::query()->whereKey($id)->firstOrFail();
        abort_unless(in_array($appointment->status, ['CONFIRMED', 'IN_PROGRESS'], true), 409, 'Lịch hẹn chưa ở trạng thái có thể điểm danh.');

        $appointment->update(['checked_in_at' => now(), 'checked_in_by' => auth()->id()]);
        $appointment->load('customer', 'specialistProfile.user');
        $notifications->send($appointment->specialistProfile->user, 'Khách đã check-in', $appointment->customer->name.' đã có mặt tại phòng chờ cho lịch '.$appointment->appointment_code.'.', 'appointment.checked_in', route('specialist.appointments.detail', $appointment), $appointment);

        return back()->with('success', 'Đã ghi nhận khách đến phòng khám.');
    }

    /**
     * SCR-STF-03 — Bảng xếp phòng tư vấn của lễ tân.
     *
     * Cho phép lễ tân theo dõi 8 phòng, xem lịch chiếm phòng trong ngày và
     * xếp phòng thủ công khi hệ thống chưa tự phân bổ được.
     */
    public function roomBoard(Request $request, RoomAllocationService $allocation): View
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = $data['date'] ?? today()->toDateString();

        $capacity = $allocation->dailyCapacity($date);
        $rooms = ConsultationRoom::query()->where('is_active', true)->orderBy('id')->pluck('name', 'id');
        $appointments = Appointment::query()
            ->whereDate('start_at', $date)
            ->whereNotNull('consultation_room_id')
            ->holdingSlot()
            ->with(['customer', 'service', 'specialistProfile.user'])
            ->orderBy('start_at')
            ->get()
            ->groupBy('consultation_room_id');

        $board = collect($capacity['rooms'])->map(function (array $room) use ($rooms, $appointments): array {
            $room['name'] = $rooms[$room['id']] ?? $room['name'];
            $room['appointments'] = $appointments[$room['id']] ?? collect();

            return $room;
        })->all();

        $unassigned = Appointment::query()
            ->whereDate('start_at', $date)
            ->whereNull('consultation_room_id')
            ->where('consultation_type', 'OFFLINE')
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->with(['customer', 'service', 'specialistProfile.user'])
            ->orderBy('start_at')
            ->get();

        return view('staff.room-board', compact('board', 'unassigned', 'date', 'capacity'));
    }

    /**
     * SCR-STF-03 — Lễ tân xếp phòng thủ công cho một lịch hẹn.
     */
    public function assignRoom(Request $request, Appointment $appointment, RoomAllocationService $allocation): RedirectResponse
    {
        $data = $request->validate([
            'consultation_room_id' => ['required', 'integer', 'exists:consultation_rooms,id'],
        ]);

        $room = ConsultationRoom::query()->where('is_active', true)->findOrFail($data['consultation_room_id']);

        $conflict = Appointment::query()
            ->whereKeyNot($appointment->id)
            ->where('consultation_room_id', $room->id)
            ->holdingSlot()
            ->where('start_at', '<', $appointment->end_at)
            ->where('end_at', '>', $appointment->start_at)
            ->exists();

        abort_if($conflict, 409, 'Phòng '.$room->name.' đang có lịch trùng khung giờ này.');

        $appointment->update([
            'consultation_room_id' => $room->id,
            'needs_attention' => false,
            'attention_reason' => null,
            'attention_flagged_at' => null,
        ]);

        return back()->with('success', 'Đã xếp '.$room->name.' cho lịch '.$appointment->appointment_code.'.');
    }

    /**
     * SCR-STF-03 — Tự động xếp phòng cho các lịch chưa có phòng.
     */
    public function autoAssignRooms(RoomAllocationService $allocation): RedirectResponse
    {
        $pending = Appointment::query()
            ->whereNull('consultation_room_id')
            ->where('consultation_type', 'OFFLINE')
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->orderBy('start_at')
            ->get();

        $assigned = 0;
        foreach ($pending as $appointment) {
            if ($allocation->allocate($appointment) !== null) {
                $assigned++;
            }
        }

        if ($assigned > 0) {
            $pending->whereNull('consultation_room_id')->each(function (Appointment $appointment): void {
                $appointment->update([
                    'needs_attention' => true,
                    'attention_reason' => AttentionService::REASON_NO_ROOM,
                    'attention_flagged_at' => now(),
                ]);
            });
        }

        return back()->with('success', $assigned > 0
            ? 'Đã xếp phòng tự động cho '.$assigned.' lịch hẹn.'
            : 'Không còn lịch nào cần xếp phòng.');
    }

    public function bookingOnsite(): View
    {
        $customers = User::query()->where('role', 'customer')->orderBy('name')->get();
        $services = Service::query()->active()->orderBy('display_order')->get();
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();

        return view('staff.booking-onsite', compact('customers', 'services', 'specialists'));
    }

    /**
     * Khung giờ còn trống cho đặt lịch tại quầy (Yêu cầu 3 — nhân viên cũng chỉ
     * thấy slot thật, không phải khung giờ đã có người đặt).
     */
    public function bookingOnsiteSlots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $service = Service::query()->active()->findOrFail($data['service_id']);
        $specialist = SpecialistProfile::query()->where('status', 'ACTIVE')->findOrFail($data['specialist_id']);
        $openSlots = $this->slots->openSlotsFor($specialist, $service, $data['date']);

        return response()->json([
            'success' => true,
            'data' => ['date' => $data['date'], 'slots' => $openSlots, 'available_count' => count($openSlots)],
            'error' => null,
        ]);
    }

    public function storeBookingOnsite(Request $request, AppointmentBookingService $booking): RedirectResponse
    {
        $data = $request->validate(['customer_id' => ['required', 'integer', 'exists:users,id'], 'service_id' => ['required', 'integer', 'exists:services,id'], 'specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id'], 'date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i'], 'payment_method' => ['required', Rule::in(['CASH', 'BANK_QR'])], 'consultation_type' => ['nullable', Rule::in(['ONLINE', 'OFFLINE'])], 'customer_note' => ['nullable', 'string', 'max:500']]);
        $customer = User::query()->where('role', 'customer')->findOrFail($data['customer_id']);
        $booking->create($customer, $data, $request->user());

        return redirect()->route('staff.appointments')->with('success', 'Đã tạo lịch tại quầy cho khách hàng.');
    }

    public function customers(Request $request): View
    {
        $query = User::query()->where('role', 'customer')->with('customerProfile')->orderBy('name');
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }
        $customers = $query->paginate(30)->withQueryString();

        return view('staff.customers', compact('customers'));
    }

    public function customerProfile(User $user): View
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        $user->load('customerProfile');
        $appointments = Appointment::query()
            ->where('customer_id', $user->id)
            ->with(['service', 'specialistProfile.user', 'consultationRoom'])
            ->orderByDesc('start_at')
            ->get();

        return view('staff.customer-profile', compact('user', 'appointments'));
    }

    public function resendActivation(User $user, AccountActivationService $activation): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        abort_unless($user->status === 'PENDING_ACTIVATION' && filled($user->email), 422, 'Khách hàng chưa đủ điều kiện nhận email kích hoạt.');
        $activation->createAndSend($user);

        return back()->with($this->activationEmailFeedback($user));
    }

    /**
     * Thông báo kết quả gửi email kích hoạt — nêu rõ lý do nếu gửi thất bại.
     */
    private function activationEmailFeedback(User $user): array    {
        $user->refresh();

        if ($user->activation_email_status === AccountActivationService::STATUS_SENT) {
            return ['success' => 'Đã gửi email kích hoạt tài khoản cho khách. Nhắc khách kiểm tra cả hộp thư spam.'];
        }

        if ($user->activation_email_status === AccountActivationService::STATUS_MISCONFIGURED) {
            return [
                'error' => 'Chưa cấu hình tài khoản gửi mail nên không gửi được email kích hoạt. '
                    .($user->activation_email_error ?? 'Cần cấu hình MAIL_USERNAME/MAIL_PASSWORD trong .env rồi chạy `php artisan config:clear`.'),
            ];
        }

        return [
            'error' => 'Không gửi được email kích hoạt: '.($user->activation_email_error ?? 'không rõ nguyên nhân'),
        ];
    }

    /**
     * Gửi mã xác thực 6 số cho khách — phương án thay thế khi khách không
     * mở được email (mail bị lọc, khách không dùng email đó nữa).
     */
    public function sendActivationCode(User $user, EmailVerificationService $verification): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        abort_unless($user->status === 'PENDING_ACTIVATION' && filled($user->email), 422, 'Khách hàng chưa đủ điều kiện nhận mã xác thực.');

        $result = $verification->resend($user, EmailVerificationService::PURPOSE_ACTIVATION);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok']
            ? 'Đã gửi mã xác thực 6 số tới '.$user->email.'. Mã hết hạn sau '.config('auth.verification.otp_expire', 15).' phút.'
            : $result['message']);
    }

    /**
     * Lễ tân xác nhận email của khách trực tiếp tại quầy khi khách không nhận
     * được email (điện thoại hỏng, email sai, khách không mang email…).
     *
     * Thao tác được ghi vào nhật ký kiểm tra để truy vết được.
     */
    public function verifyCustomerManually(Request $request, User $user, AccountActivationService $activation, AuditService $audit): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Vui lòng ghi rõ lý do xác nhận thủ công để phòng khám truy vết được.',
        ]);

        $activation->activateManually($user, $data['password']);
        $audit->record('customer.email_verified_manually', $user, new: [
            'email_verified_via' => 'STAFF',
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Đã xác thực email cho khách '.$user->name.' theo phương án thủ công. Khách có thể đăng nhập ngay bằng mật khẩu vừa đặt.');
    }

    public function storeCustomer(Request $request, AuditService $audit, AccountActivationService $activation): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['nullable', 'email', 'unique:users,email'], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'gender' => ['nullable', Rule::in(['MALE', 'FEMALE', 'OTHER'])], 'address' => ['nullable', 'string', 'max:255'], 'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
        $user = DB::transaction(function () use ($data, $audit): User {
            $user = User::query()->create(['name' => $data['name'], 'email' => strtolower((string) ($data['email'] ?? '')), 'phone' => $data['phone'] ?? null, 'gender' => $data['gender'] ?? null, 'address' => $data['address'] ?? null, 'password' => Hash::make(Str::random(40)), 'role' => 'customer', 'status' => $data['email'] ? 'PENDING_ACTIVATION' : 'ACTIVE', 'email_verified_at' => null]);
            CustomerProfile::query()->create(['user_id' => $user->id, 'customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $data['date_of_birth'], 'privacy_policy_version' => 'staff-created', 'consented_at' => now()]);
            $audit->record('customer.created', $user, new: ['role' => 'customer']);

            return $user;
        });

        if ($user->status === 'PENDING_ACTIVATION') {
            $activation->createAndSend($user);

            return back()->with($this->activationEmailFeedback($user));
        }

        return back()->with('success', 'Đã tạo hồ sơ khách. Khách chưa có email nên chưa thể đăng nhập.');
    }

    public function updateCustomer(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'gender' => ['nullable', Rule::in(['MALE', 'FEMALE', 'OTHER'])], 'address' => ['nullable', 'string', 'max:255'], 'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
        $user->update(collect($data)->except('date_of_birth')->all());
        CustomerProfile::query()->updateOrCreate(['user_id' => $user->id], ['customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $data['date_of_birth'] ?? null]);
        $audit->record('customer.updated', $user, new: ['email' => $user->email]);

        return back()->with('success', 'Đã cập nhật hồ sơ khách.');
    }

    public function confirmAppointment(Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $workflow->confirm($appointment, auth()->user());

        return back()->with('success', 'Đã xác nhận lịch hẹn.');
    }

    /**
     * SCR-STF-03 — Chi tiết lịch hẹn để điều phối.
     */
    public function appointmentDetail(Appointment $appointment): View
    {
        $appointment->load([
            'customer', 'service', 'specialistProfile.user', 'consultationRoom',
            'statusHistory', 'refundRequest', 'paymentTransactions', 'rescheduledFrom', 'rescheduleRequests', 'createdBy',
        ]);
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();

        return view('staff.appointment-detail', compact('appointment', 'specialists'));
    }

    /**
     * SCR-STF-07 — Điều phối lịch bị ảnh hưởng.
     */
    public function attentionList(Request $request): View
    {
        $data = $request->validate([
            'specialist_id' => ['nullable', 'integer', 'exists:specialist_profiles,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = Appointment::query()
            ->where('needs_attention', true)
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->with(['customer', 'service', 'specialistProfile.user', 'consultationRoom'])
            ->orderBy('start_at');
        $query->when($data['specialist_id'] ?? null, fn ($builder, int $specialistId) => $builder->where('specialist_profile_id', $specialistId));
        $query->when($data['date'] ?? null, fn ($builder, string $date) => $builder->whereDate('start_at', $date));

        $appointments = $query->get();
        $groupedByReason = $appointments->groupBy(fn (Appointment $appointment): string => $appointment->attention_reason ?? 'Lý do khác');
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();

        return view('staff.attention', compact('appointments', 'groupedByReason', 'specialists', 'data'));
    }

    public function reassignAppointment(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id']]);
        $workflow->reassignSpecialist($appointment, SpecialistProfile::query()->findOrFail($data['specialist_id']), $request->user());

        return back()->with('success', 'Đã chuyển lịch sang chuyên viên mới và gửi thông báo liên quan.');
    }

    public function cancelAppointment(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $workflow->cancelByClinic($appointment, $request->user(), $data['reason']);

        return back()->with('success', 'Đã hủy lịch theo yêu cầu phòng khám.');
    }

    public function resolveReschedule(Request $request, RescheduleRequest $rescheduleRequest, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['nullable', 'string', 'max:500']]);
        $workflow->resolveReschedule($rescheduleRequest, $request->user(), $data['decision'] === 'approve', $data['reason'] ?? null);

        return back()->with('success', 'Đã xử lý yêu cầu đổi lịch.');
    }

    /**
     * SCR-STF-03 — Bắt đầu buổi tư vấn hộ khách.
     */
    public function startAppointment(Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $workflow->start($appointment, auth()->user());

        return back()->with('success', 'Đã bắt đầu buổi tư vấn.');
    }

    /**
     * SCR-STF-03 — Đánh dấu khách vắng mặt.
     */
    public function noShowAppointment(Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $workflow->markNoShow($appointment, auth()->user());

        return back()->with('success', 'Đã đánh dấu khách vắng mặt.');
    }

    /**
     * SCR-STF-03 / SCR-STF-07 — Đổi lịch do phòng khám thực hiện.
     */
    public function rescheduleAppointment(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $workflow->rescheduleByClinic(
            $appointment,
            CarbonImmutable::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time']),
            $request->user(),
            $data['reason']
        );

        return back()->with('success', 'Đã đổi lịch và gửi thông báo cho khách.');
    }

    /**
     * SCR-STF-03 — Gửi thông báo thủ công cho khách của lịch.
     */
    public function notifyAppointment(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);
        $workflow->notifyCustomer($appointment, $request->user(), $data['message']);

        return back()->with('success', 'Đã gửi thông báo cho khách hàng.');
    }

    public function payments(): View
    {
        $appointments = Appointment::query()
            ->whereIn('status', self::CASH_COLLECTIBLE_STATUSES)
            ->where('payment_status', 'UNPAID')
            ->where('payment_method', 'CASH')
            ->with(['customer', 'specialistProfile.user', 'service'])
            ->orderByRaw("CASE status
                WHEN 'PENDING' THEN 1
                WHEN 'CONFIRMED' THEN 2
                WHEN 'IN_PROGRESS' THEN 3
                ELSE 4
            END")
            ->orderBy('start_at')
            ->paginate(20);

        $refunds = RefundRequest::query()->where('status', 'PENDING')->with(['appointment.customer', 'appointment.service'])->latest()->get();

        return view('staff.payments', compact('appointments', 'refunds'));
    }

    public function collectPayment(CollectPaymentRequest $request, int $id): RedirectResponse
    {
        $appointment = DB::transaction(function () use ($id, $request): Appointment {
            $appointment = Appointment::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($appointment->status, self::CASH_COLLECTIBLE_STATUSES, true),
                409,
                'Lịch hẹn không đủ điều kiện xác nhận thu tiền. Chỉ thu được khi lịch ở trạng thái chờ xác nhận, đã xác nhận, đang tư vấn hoặc đã hoàn tất.'
            );

            abort_unless(
                $appointment->payment_status === 'UNPAID' && $appointment->payment_method === 'CASH',
                409,
                'Lịch hẹn không dùng thanh toán tiền mặt hoặc đã được thanh toán.'
            );

            $appointment->update([
                'payment_status' => 'PAID',
                'collected_at' => now(),
                'collected_by' => auth()->id(),
                'payment_reference' => $request->validated('payment_reference'),
            ]);

            return $appointment;
        });

        return back()->with('success', $this->collectPaymentMessage($appointment));
    }

    private function collectPaymentMessage(Appointment $appointment): string
    {
        $timing = match ($appointment->status) {
            'PENDING' => 'trước giờ tư vấn',
            'CONFIRMED' => 'trước giờ tư vấn',
            'IN_PROGRESS' => 'trong buổi tư vấn',
            default => 'sau buổi tư vấn',
        };

        return "Đã xác nhận thu tiền mặt ({$timing}) cho lịch hẹn #{$appointment->id}.";
    }

    public function markRefunded(Request $request, RefundRequest $refundRequest, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:100']]);
        $payments->markRefunded($refundRequest, $request->user(), $data['reference']);

        return back()->with('success', 'Đã xác nhận hoàn tiền cho khách.');
    }
}
