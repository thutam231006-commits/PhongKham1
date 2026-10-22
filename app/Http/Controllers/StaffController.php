<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectPaymentRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function payments(): View
    {
        $appointments = Appointment::query()
            ->where('status', 'COMPLETED')
            ->where('payment_status', 'UNPAID')
            ->where('payment_method', 'CASH')
            ->with(['customer', 'specialistProfile.user', 'service'])
            ->latest('completed_at')
            ->paginate(20);

        return view('staff.payments', compact('appointments'));
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
}
