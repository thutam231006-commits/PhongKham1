<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectPaymentRequest;
use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\RefundRequest;
use App\Models\RescheduleRequest;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Services\AccountActivationService;
use App\Services\AppointmentBookingService;
use App\Services\AppointmentWorkflowService;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function dashboard(Request $request, NotificationService $notifications): View
    {
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $today = Appointment::query()->whereDate('start_at', $date)->with(['customer', 'specialistProfile.user', 'service'])->orderBy('start_at')->get();
        $cashToCollect = Appointment::query()->where('status', 'COMPLETED')->where('payment_status', 'UNPAID')->where('payment_method', 'CASH')->count();
        $needsAttention = Appointment::query()->where('needs_attention', true)->count();
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

        return view('staff.dashboard', compact('date', 'today', 'cashToCollect', 'needsAttention', 'pending', 'pendingCheckins', 'rescheduleRequests', 'refundsPending', 'newCustomers', 'unreadNotifications', 'notifications', 'urgentAppointments'));
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

        return view('staff.appointment-list', compact('appointments', 'specialists', 'services', 'data'));
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

    public function markCheckin(int $id): RedirectResponse
    {
        $appointment = Appointment::query()->whereKey($id)->firstOrFail();
        abort_unless(in_array($appointment->status, ['CONFIRMED', 'IN_PROGRESS'], true), 409, 'Lịch hẹn chưa ở trạng thái có thể điểm danh.');

        $appointment->update(['checked_in_at' => now(), 'checked_in_by' => auth()->id()]);

        return back()->with('success', 'Đã ghi nhận khách đến phòng khám.');
    }

    public function bookingOnsite(): View
    {
        $customers = User::query()->where('role', 'customer')->orderBy('name')->get();
        $services = Service::query()->active()->orderBy('display_order')->get();
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->orderBy('display_order')->get();

        return view('staff.booking-onsite', compact('customers', 'services', 'specialists'));
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

        return back()->with('success', 'Đã gửi lại email kích hoạt tài khoản.');
    }

    public function storeCustomer(Request $request, AuditService $audit, AccountActivationService $activation): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['nullable', 'email', 'unique:users,email'], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
        $user = DB::transaction(function () use ($data, $audit): User {
            $user = User::query()->create(['name' => $data['name'], 'email' => strtolower((string) ($data['email'] ?? '')), 'phone' => $data['phone'] ?? null, 'password' => Hash::make(Str::random(40)), 'role' => 'customer', 'status' => $data['email'] ? 'PENDING_ACTIVATION' : 'ACTIVE', 'email_verified_at' => null]);
            CustomerProfile::query()->create(['user_id' => $user->id, 'customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $data['date_of_birth'], 'privacy_policy_version' => 'staff-created', 'consented_at' => now()]);
            $audit->record('customer.created', $user, new: ['role' => 'customer']);

            return $user;
        });

        if ($user->status === 'PENDING_ACTIVATION') {
            $activation->createAndSend($user);
        }

        return back()->with('success', $user->status === 'PENDING_ACTIVATION' ? 'Đã tạo hồ sơ và gửi email kích hoạt tài khoản cho khách.' : 'Đã tạo hồ sơ khách. Khách chưa có email nên chưa thể đăng nhập.');
    }

    public function updateCustomer(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
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
            ->where('status', 'COMPLETED')
            ->where('payment_status', 'UNPAID')
            ->where('payment_method', 'CASH')
            ->with(['customer', 'specialistProfile.user', 'service'])
            ->latest('completed_at')
            ->paginate(20);

        $refunds = RefundRequest::query()->where('status', 'PENDING')->with(['appointment.customer', 'appointment.service'])->latest()->get();

        return view('staff.payments', compact('appointments', 'refunds'));
    }

    public function collectPayment(CollectPaymentRequest $request, int $id): RedirectResponse
    {
        DB::transaction(function () use ($id, $request): void {
            $appointment = Appointment::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($appointment->status === 'COMPLETED' && $appointment->payment_status === 'UNPAID' && $appointment->payment_method === 'CASH', 409, 'Lịch hẹn không đủ điều kiện xác nhận thu tiền.');
            $appointment->update([
                'payment_status' => 'PAID',
                'collected_at' => now(),
                'collected_by' => auth()->id(),
                'payment_reference' => $request->validated('payment_reference'),
            ]);
        });

        return back()->with('success', 'Đã xác nhận thu tiền mặt cho lịch hẹn.');
    }

    public function markRefunded(Request $request, RefundRequest $refundRequest, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:100']]);
        $payments->markRefunded($refundRequest, $request->user(), $data['reference']);

        return back()->with('success', 'Đã xác nhận hoàn tiền cho khách.');
    }
}
