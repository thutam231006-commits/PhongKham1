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
use App\Services\AppointmentBookingService;
use App\Services\AppointmentWorkflowService;
use App\Services\AuditService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function dashboard(): View
    {
        $today = Appointment::query()->whereDate('start_at', today())->with(['customer', 'specialistProfile.user', 'service'])->orderBy('start_at')->get();
        $cashToCollect = Appointment::query()->where('status', 'COMPLETED')->where('payment_status', 'UNPAID')->where('payment_method', 'CASH')->count();
        $needsAttention = Appointment::query()->where('needs_attention', true)->count();
        $pending = Appointment::query()->where('status', 'PENDING')->count();

        return view('staff.dashboard', compact('today', 'cashToCollect', 'needsAttention', 'pending'));
    }

    public function checkin(Request $request): View
    {
        $query = Appointment::query()->whereDate('start_at', today())->with(['customer', 'specialistProfile.user', 'service'])->orderBy('start_at');
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($builder) use ($term): void {
                $builder->where('appointment_code', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
            });
        }
        $appointments = $query->paginate(20)->withQueryString();

        return view('staff.checkin', compact('appointments'));
    }

    public function appointments(Request $request): View
    {
        $query = Appointment::query()->with(['customer', 'specialistProfile.user', 'service'])->orderBy('start_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->boolean('attention')) {
            $query->where('needs_attention', true);
        }
        $appointments = $query->paginate(30)->withQueryString();
        $rescheduleRequests = RescheduleRequest::query()->where('status', 'PENDING')->with(['appointment.customer', 'appointment.specialistProfile.user'])->latest()->get();

        return view('staff.appointments', compact('appointments', 'rescheduleRequests'));
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
        $data = $request->validate(['customer_id' => ['required', 'integer', 'exists:users,id'], 'service_id' => ['required', 'integer', 'exists:services,id'], 'specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id'], 'date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i'], 'payment_method' => ['required', Rule::in(['CASH', 'BANK_QR'])], 'customer_note' => ['nullable', 'string', 'max:500']]);
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

    public function storeCustomer(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['required', 'email', 'unique:users,email'], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
        DB::transaction(function () use ($data, $audit): void {
            $user = User::query()->create(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'] ?? null, 'password' => Hash::make('MindGuide@2026'), 'role' => 'customer', 'status' => 'ACTIVE', 'email_verified_at' => now()]);
            CustomerProfile::query()->create(['user_id' => $user->id, 'customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => $data['date_of_birth'], 'privacy_policy_version' => 'staff-created', 'consented_at' => now()]);
            $audit->record('customer.created', $user, new: ['role' => 'customer']);
        });

        return back()->with('success', 'Đã tạo hồ sơ khách. Mật khẩu tạm thời: MindGuide@2026.');
    }

    public function updateCustomer(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        abort_unless(strtolower((string) $user->role) === 'customer', 404);
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)], 'phone' => ['nullable', 'regex:/^\+?[0-9]{9,15}$/'], 'date_of_birth' => ['nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()]]);
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
