<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminServiceRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $appointments = Appointment::query()->whereBetween('start_at', [$from, $to]);
        $statusCounts = (clone $appointments)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $revenue = (float) (clone $appointments)->where('payment_status', 'PAID')->sum('price');
        $refunds = (float) (clone $appointments)->where('payment_status', 'REFUNDED')->sum('price');

        $stats = [
            'customers' => User::query()->where('role', 'customer')->count(),
            'active_specialists' => SpecialistProfile::query()->where('status', 'ACTIVE')->count(),
            'appointments' => (clone $appointments)->count(),
            'today' => Appointment::query()->whereDate('start_at', today())->count(),
            'needs_attention' => Appointment::query()->where('needs_attention', true)->count(),
            'cash_pending' => Appointment::query()->where('status', 'COMPLETED')->where('payment_method', 'CASH')->where('payment_status', 'UNPAID')->count(),
            'net_revenue' => $revenue - $refunds,
        ];

        return view('admin.dashboard', compact('stats', 'statusCounts', 'from', 'to'));
    }

    public function users(Request $request): View
    {
        $query = User::query()->orderBy('name');
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function storeUser(StoreAdminUserRequest $request): RedirectResponse
    {
        $user = User::create([...$request->validated(), 'password' => bcrypt('password'), 'email_verified_at' => now()]);
        $this->audit('user.created', 'users', $user->id, null, ['role' => $user->role, 'status' => $user->status]);

        return back()->with('success', 'Đã tạo tài khoản người dùng. Mật khẩu mặc định là password.');
    }

    public function updateUser(StoreAdminUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()) && $request->validated('role') !== $user->role, 422, 'Không thể tự đổi vai trò của chính mình.');
        $old = ['role' => $user->role, 'status' => $user->status];
        $user->update($request->validated());
        $this->audit('user.updated', 'users', $user->id, $old, ['role' => $user->role, 'status' => $user->status]);

        return back()->with('success', 'Đã cập nhật tài khoản.');
    }

    public function specialists(): View
    {
        $specialists = SpecialistProfile::query()->with(['user', 'services'])->withCount('appointments')->orderBy('display_order')->paginate(20);

        return view('admin.specialists', compact('specialists'));
    }

    public function toggleSpecialist(SpecialistProfile $specialistProfile): RedirectResponse
    {
        $newStatus = $specialistProfile->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if ($newStatus === 'ACTIVE' && ($specialistProfile->services()->count() === 0 || $specialistProfile->workingSchedules()->where('is_active', true)->count() === 0)) {
            return back()->withErrors(['specialist' => 'Chuyên viên cần có ít nhất một dịch vụ và một ca làm việc trước khi kích hoạt.']);
        }
        $old = ['status' => $specialistProfile->status];
        $specialistProfile->update(['status' => $newStatus]);
        $this->audit('specialist.status_changed', 'specialist_profiles', $specialistProfile->id, $old, ['status' => $newStatus]);

        return back()->with('success', 'Đã cập nhật trạng thái chuyên viên.');
    }

    public function services(Request $request): View
    {
        $services = Service::query()->with('category')->orderBy('display_order')->paginate(20);
        $categories = ServiceCategory::query()->where('is_active', true)->orderBy('display_order')->get();

        return view('admin.services', compact('services', 'categories'));
    }

    public function storeService(StoreAdminServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());
        $this->audit('service.created', 'services', $service->id, null, ['name' => $service->name, 'status' => $service->status]);

        return back()->with('success', 'Đã tạo dịch vụ.');
    }

    public function updateService(StoreAdminServiceRequest $request, Service $service): RedirectResponse
    {
        $old = $service->only(['category_id', 'name', 'duration_minutes', 'price', 'status']);
        $service->update($request->validated());
        $this->audit('service.updated', 'services', $service->id, $old, $service->only(['category_id', 'name', 'duration_minutes', 'price', 'status']));

        return back()->with('success', 'Đã cập nhật dịch vụ.');
    }

    public function reports(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $base = Appointment::query()->whereBetween('start_at', [$from, $to]);
        $byStatus = (clone $base)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $byService = (clone $base)->join('services', 'services.id', '=', 'appointments.service_id')->selectRaw('services.name, COUNT(*) AS total')->groupBy('services.id', 'services.name')->orderByDesc('total')->get();
        $byDay = (clone $base)->selectRaw('DATE(start_at) AS day, COUNT(*) AS total')->groupBy('day')->orderBy('day')->get();

        return view('admin.reports', compact('from', 'to', 'byStatus', 'byService', 'byDay'));
    }

    public function auditLogs(Request $request): View
    {
        $query = AuditLog::query()->with('actor')->latest('created_at');
        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action')->toString().'%');
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        $logs = $query->paginate(30)->withQueryString();

        return view('admin.audit-logs', compact('logs'));
    }

    /** @return array{0: Carbon\CarbonImmutable, 1: Carbon\CarbonImmutable} */
    private function dateRange(Request $request): array
    {
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $from = $request->date('from')?->startOfDay() ?? now()->subDays(29)->startOfDay();

        return [$from, $to];
    }

    /** @param array<string, mixed>|null $old @param array<string, mixed>|null $new */
    private function audit(string $action, string $entityType, int $entityId, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'actor_id' => auth()->id(),
            'actor_role' => strtoupper((string) auth()->user()?->role),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $old,
            'new_value' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
