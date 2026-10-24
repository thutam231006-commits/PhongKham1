<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminServiceRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Role;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Services\AppointmentTrafficService;
use App\Services\AppointmentWorkflowService;
use App\Services\AttentionService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(Request $request, AdminAnalyticsService $analytics): View
    {
        $period = $analytics->period($request->input('from'), $request->input('to'));
        $stats = array_merge($analytics->summary($period), [
            'customers' => User::query()->where('role', 'customer')->count(),
            'active_specialists' => SpecialistProfile::query()->where('status', 'ACTIVE')->count(),
        ]);
        $statusCounts = $analytics->statusCounts($period);
        $dailyRevenue = $analytics->dailyRevenue($period);
        $serviceCounts = $analytics->serviceCounts($period);
        $from = $period['from'];
        $to = $period['to'];

        return view('admin.dashboard', compact('stats', 'statusCounts', 'dailyRevenue', 'serviceCounts', 'from', 'to'));
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
        $user = User::create([...$request->validated(), 'password' => bcrypt('MindGuide@2026'), 'email_verified_at' => now()]);
        $this->audit('user.created', 'users', $user->id, null, ['role' => $user->role, 'status' => $user->status]);

        return back()->with('success', 'Đã tạo tài khoản người dùng. Mật khẩu tạm thời là MindGuide@2026.');
    }

    public function updateUser(StoreAdminUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()) && ($request->validated('role') !== $user->role || $request->validated('status') !== 'ACTIVE'), 422, 'Không thể tự hạ quyền hoặc vô hiệu hóa chính mình.');
        if ($user->role === 'admin' && $request->validated('status') !== 'ACTIVE') {
            abort_if(User::query()->where('role', 'admin')->where('status', 'ACTIVE')->whereKeyNot($user->id)->doesntExist(), 422, 'Hệ thống phải luôn còn ít nhất một Admin đang hoạt động.');
        }
        $old = ['role' => $user->role, 'status' => $user->status];
        $user->update($request->validated());
        $this->audit('user.updated', 'users', $user->id, $old, ['role' => $user->role, 'status' => $user->status]);

        return back()->with('success', 'Đã cập nhật tài khoản.');
    }

    /**
     * Khóa / vô hiệu hóa / kích hoạt lại tài khoản — SCR-ADM-02.
     *
     * Bắt buộc có lý do và hủy toàn bộ phiên đang đăng nhập của người đó.
     */
    public function changeUserStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'LOCKED', 'DISABLED'])],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        abort_if($user->is(auth()->user()), 422, 'Không thể thay đổi trạng thái tài khoản của chính mình.');
        if ($user->role === 'admin' && $data['status'] !== 'ACTIVE') {
            abort_if(User::query()->where('role', 'admin')->where('status', 'ACTIVE')->whereKeyNot($user->id)->doesntExist(), 422, 'Hệ thống phải còn ít nhất một quản trị viên đang hoạt động.');
        }

        $old = ['status' => $user->status];
        $user->update(['status' => $data['status']]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => null, 'failed_login_count' => 0, 'locked_until' => null])->saveQuietly();
        $this->audit('user.status_changed', 'users', $user->id, $old, ['status' => $user->status, 'reason' => $data['reason']]);

        return back()->with('success', 'Đã cập nhật trạng thái tài khoản. Mọi phiên đang đăng nhập của người này đã bị hủy.');
    }

    /**
     * Gửi liên kết đặt lại mật khẩu cho người dùng — SCR-ADM-02.
     */
    public function sendPasswordResetLink(Request $request, User $user): RedirectResponse
    {
        PasswordBroker::sendResetLink(['email' => $user->email]);
        $this->audit('user.password_reset_requested', 'users', $user->id, null, ['email' => $user->email]);

        return back()->with('success', 'Đã gửi liên kết đặt lại mật khẩu tới email của người dùng.');
    }

    public function specialists(): View
    {
        $specialists = SpecialistProfile::query()->with(['user', 'services'])->withCount('appointments')->orderBy('display_order')->paginate(20);
        $services = Service::query()->where('status', 'ACTIVE')->orderBy('name')->get();

        return view('admin.specialists', compact('specialists', 'services'));
    }

    public function storeSpecialist(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'unique:users,email'], 'title' => ['required', 'string', 'max:100'], 'expertise' => ['nullable', 'string', 'max:255'], 'credentials' => ['nullable', 'string', 'max:2000'], 'bio' => ['nullable', 'string', 'max:3000'], 'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:70'], 'service_ids' => ['required', 'array', 'min:1'], 'service_ids.*' => ['integer', 'exists:services,id'], 'avatar' => ['nullable', 'image', 'max:2048']]);

        DB::transaction(function () use ($request, $data): void {
            $user = User::query()->create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => Hash::make('MindGuide@2026'), 'role' => 'specialist', 'status' => 'ACTIVE', 'email_verified_at' => now()]);
            $avatarUrl = $request->file('avatar')?->store('specialists', 'public');
            $profile = SpecialistProfile::query()->create(['user_id' => $user->id, 'title' => $data['title'], 'expertise' => $data['expertise'] ?? null, 'credentials' => $data['credentials'] ?? null, 'bio' => $data['bio'] ?? null, 'years_of_experience' => $data['years_of_experience'] ?? null, 'avatar_url' => $avatarUrl ? Storage::url($avatarUrl) : null, 'status' => 'INACTIVE']);
            $profile->services()->sync($data['service_ids']);
            $this->audit('specialist.created', 'specialist_profiles', $profile->id, null, ['user_id' => $user->id]);
        });

        return back()->with('success', 'Đã tạo chuyên viên. Mật khẩu tạm thời: MindGuide@2026.');
    }

    public function updateSpecialist(Request $request, SpecialistProfile $specialistProfile): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'title' => ['required', 'string', 'max:100'], 'expertise' => ['nullable', 'string', 'max:255'], 'credentials' => ['nullable', 'string', 'max:2000'], 'bio' => ['nullable', 'string', 'max:3000'], 'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:70'], 'service_ids' => ['required', 'array', 'min:1'], 'service_ids.*' => ['integer', 'exists:services,id'], 'avatar' => ['nullable', 'image', 'max:2048']]);
        DB::transaction(function () use ($request, $data, $specialistProfile): void {
            $specialistProfile->user()->update(['name' => $data['name']]);
            $attributes = collect($data)->except(['name', 'service_ids', 'avatar'])->all();
            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('specialists', 'public');
                $attributes['avatar_url'] = Storage::url($path);
            }
            $specialistProfile->update($attributes);
            $specialistProfile->services()->sync($data['service_ids']);
            $this->audit('specialist.updated', 'specialist_profiles', $specialistProfile->id, null, ['services' => $data['service_ids']]);
        });

        return back()->with('success', 'Đã cập nhật hồ sơ chuyên viên.');
    }

    public function toggleSpecialist(SpecialistProfile $specialistProfile, AttentionService $attention): RedirectResponse
    {
        $newStatus = $specialistProfile->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if ($newStatus === 'ACTIVE' && ($specialistProfile->services()->count() === 0 || $specialistProfile->workingSchedules()->where('is_active', true)->count() === 0)) {
            return back()->withErrors(['specialist' => 'Chuyên viên cần có ít nhất một dịch vụ và một ca làm việc trước khi kích hoạt.']);
        }
        $old = ['status' => $specialistProfile->status];
        $specialistProfile->update(['status' => $newStatus]);
        if ($newStatus === 'INACTIVE') {
            $attention->flag(
                $specialistProfile->appointments()->holdingSlot()->where('start_at', '>', now()),
                AttentionService::REASON_SPECIALIST_INACTIVE
            );
        }
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

    public function reports(Request $request, AdminAnalyticsService $analytics, AppointmentTrafficService $traffic): View
    {
        $period = $analytics->period($request->input('from'), $request->input('to'));
        $from = $period['from'];
        $to = $period['to'];
        $byStatus = $analytics->statusCounts($period);
        $byService = $analytics->serviceCounts($period);
        $byDay = Appointment::query()->whereBetween('start_at', [$from, $to])->selectRaw('DATE(start_at) AS day, COUNT(*) AS total')->groupBy('day')->orderBy('day')->get();
        $financial = $analytics->summary($period);

        // Yêu cầu 1: lưu lượng theo thời gian — theo ngày và theo khung giờ trong ngày.
        $patientsByDay = $traffic->dailyPatients($from, $to);
        $hourlyTraffic = $traffic->byHourOfDay($to->toDateString());
        $peakHour = $hourlyTraffic->sortByDesc('total')->first();

        return view('admin.reports', compact('from', 'to', 'byStatus', 'byService', 'byDay', 'financial', 'patientsByDay', 'hourlyTraffic', 'peakHour'));
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

    public function categories(): View
    {
        $categories = ServiceCategory::query()->withCount('services')->orderBy('display_order')->get();

        return view('admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:service_categories,name'], 'description' => ['nullable', 'string', 'max:500'], 'display_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['required', 'boolean']]);
        $category = ServiceCategory::query()->create($data);
        $this->audit('category.created', 'service_categories', $category->id, null, $category->only(['name', 'is_active']));

        return back()->with('success', 'Đã tạo danh mục dịch vụ.');
    }

    public function updateCategory(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('service_categories', 'name')->ignore($serviceCategory->id)], 'description' => ['nullable', 'string', 'max:500'], 'display_order' => ['nullable', 'integer', 'min:0'], 'is_active' => ['required', 'boolean']]);
        $serviceCategory->update($data);
        $this->audit('category.updated', 'service_categories', $serviceCategory->id, null, $serviceCategory->only(['name', 'is_active']));

        return back()->with('success', 'Đã cập nhật danh mục.');
    }

    public function schedule(): View
    {
        $exceptions = ScheduleException::query()->with('specialistProfile.user')->latest('start_date')->paginate(20);
        $specialists = SpecialistProfile::query()->with('user')->where('status', 'ACTIVE')->get();

        return view('admin.schedule', compact('exceptions', 'specialists'));
    }

    public function storeScheduleException(Request $request, AttentionService $attention): RedirectResponse
    {
        $data = $request->validate(['specialist_profile_id' => ['nullable', 'integer', 'exists:specialist_profiles,id'], 'type' => ['required', Rule::in(['HOLIDAY', 'DAY_OFF', 'TIME_BLOCK'])], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'start_time' => ['nullable', 'date_format:H:i'], 'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'], 'reason' => ['required', 'string', 'max:255'], 'is_emergency' => ['nullable', 'boolean']]);
        $exception = ScheduleException::query()->create([...$data, 'created_by' => auth()->id(), 'is_emergency' => (bool) ($data['is_emergency'] ?? false)]);
        $attention->flag(
            Appointment::query()->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])->whereBetween('start_at', [$exception->start_date->startOfDay(), $exception->end_date->endOfDay()])->when($exception->specialist_profile_id, fn ($query) => $query->where('specialist_profile_id', $exception->specialist_profile_id)),
            AttentionService::REASON_SCHEDULE_EXCEPTION
        );
        $this->audit('schedule_exception.created', 'schedule_exceptions', $exception->id, null, $exception->only(['type', 'start_date', 'end_date']));

        return back()->with('success', 'Đã lưu ngày nghỉ/ngày lễ và đánh dấu các lịch bị ảnh hưởng.');
    }

    public function appointments(Request $request): View
    {
        $query = Appointment::query()->with(['customer', 'service', 'specialistProfile.user'])->latest('start_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        $appointments = $query->paginate(30)->withQueryString();

        return view('admin.appointments', compact('appointments'));
    }

    public function cancelAppointment(Request $request, Appointment $appointment, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $workflow->cancelByClinic($appointment, $request->user(), $data['reason']);

        return back()->with('success', 'Đã hủy lịch và tạo yêu cầu hoàn tiền khi cần.');
    }

    public function reviews(): View
    {
        $reviews = Review::query()->with(['appointment', 'specialistProfile.user'])->latest()->paginate(30);

        return view('admin.reviews', compact('reviews'));
    }

    public function moderateReview(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['VISIBLE', 'HIDDEN'])], 'reason' => ['required_if:status,HIDDEN', 'nullable', 'string', 'max:500']]);
        $review->update(['status' => $data['status'], 'moderation_reason' => $data['reason'] ?? null, 'moderated_by' => auth()->id(), 'moderated_at' => now()]);
        $this->audit('review.moderated', 'reviews', $review->id, null, ['status' => $review->status]);

        return back()->with('success', 'Đã cập nhật trạng thái đánh giá.');
    }

    public function notifications(): View
    {
        $users = User::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'email', 'role']);

        return view('admin.notifications', compact('users'));
    }

    public function sendNotification(Request $request, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate(['audience' => ['required', Rule::in(['all', 'customer', 'specialist', 'staff', 'admin'])], 'title' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'max:1000']]);
        $query = User::query()->where('status', 'ACTIVE');
        if ($data['audience'] !== 'all') {
            $query->where('role', $data['audience']);
        }
        $recipients = $query->get();
        $notifications->sendMany($recipients, $data['title'], $data['message'], 'manual');
        $this->audit('notification.manual_sent', 'users', 0, null, ['audience' => $data['audience'], 'recipient_count' => $recipients->count()]);

        return back()->with('success', 'Đã gửi thông báo cho '.$recipients->count().' tài khoản.');
    }

    public function settings(): View
    {
        $settings = SystemSetting::query()->orderBy('group')->orderBy('key')->get();

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['settings' => ['required', 'array'], 'settings.*' => ['nullable', 'string', 'max:500']]);
        foreach ($data['settings'] as $key => $value) {
            SystemSetting::query()->whereKey($key)->update(['value' => $value]);
        }
        $this->audit('settings.updated', 'system_settings', 0, null, ['keys' => array_keys($data['settings'])]);

        return back()->with('success', 'Đã cập nhật cấu hình hệ thống.');
    }

    public function roles(): View
    {
        $roles = Role::query()->with('permissions')->get();
        $permissions = Permission::query()->orderBy('group')->orderBy('code')->get();

        return view('admin.roles', compact('roles', 'permissions'));
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate(['permission_ids' => ['nullable', 'array'], 'permission_ids.*' => ['integer', 'exists:permissions,id']]);
        $role->permissions()->sync($data['permission_ids'] ?? []);
        $this->audit('role.permissions_updated', 'roles', $role->id, null, ['permission_ids' => $data['permission_ids'] ?? []]);

        return back()->with('success', 'Đã cập nhật quyền cho vai trò.');
    }

    public function payments(): View
    {
        $refunds = RefundRequest::query()->with(['appointment.customer', 'appointment.service'])->latest()->paginate(30);

        return view('admin.payments', compact('refunds'));
    }

    public function markRefunded(Request $request, RefundRequest $refundRequest, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:100']]);
        $payments->markRefunded($refundRequest, $request->user(), $data['reference']);

        return back()->with('success', 'Đã xác nhận hoàn tiền.');
    }

    public function exportReport(Request $request, AdminAnalyticsService $analytics): StreamedResponse
    {
        $period = $analytics->period($request->input('from'), $request->input('to'));
        $rows = Appointment::query()->whereBetween('start_at', [$period['from'], $period['to']])->selectRaw('DATE(start_at) AS day, status, COUNT(*) AS total, SUM(CASE WHEN payment_status = "PAID" THEN price ELSE 0 END) AS collected')->groupBy('day', 'status')->orderBy('day')->get();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Ngày', 'Trạng thái', 'Số lịch', 'Đã thu']);
            foreach ($rows as $row) {
                fputcsv($output, [$this->csvSafe($row->day), $this->csvSafe($row->status), $row->total, $row->collected]);
            }
            fclose($output);
        }, 'mind-guide-report-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function placeholder(string $section): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.placeholder', ['section' => $section]);
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

    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
