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
use App\Services\MailHealthService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
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
        $trend = $analytics->trend($period);
        $statusCounts = $analytics->statusCounts($period);
        $dailyRevenue = $analytics->dailyRevenue($period);
        $serviceCounts = $analytics->serviceCounts($period);
        $workQueue = $analytics->workQueue();
        $from = $period['from'];
        $to = $period['to'];

        $upcomingAppointments = Appointment::query()
            ->with(['customer', 'service', 'specialistProfile.user'])
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(6)
            ->get();

        $attentionAppointments = Appointment::query()
            ->with(['customer', 'service', 'specialistProfile.user'])
            ->where('needs_attention', true)
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'trend', 'statusCounts', 'dailyRevenue', 'serviceCounts',
            'workQueue', 'upcomingAppointments', 'attentionAppointments', 'from', 'to'
        ));
    }

    public function users(Request $request): View
    {
        $query = User::query()->orderBy('name');
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $users = $query->paginate(20)->withQueryString();

        $roleCounts = User::query()->selectRaw('role, COUNT(*) AS total')->groupBy('role')->pluck('total', 'role');
        $statusCounts = User::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return view('admin.users', compact('users', 'roleCounts', 'statusCounts'));
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

    /**
     * Hồ sơ chuyên viên — đặc tả §2.3.
     *
     * Màn hình tách rõ nhóm thông tin CÔNG KHAI (khách nhìn thấy) và NỘI BỘ
     * (chỉ quản trị). Trước khi ngừng hoạt động, giao diện phải hiển thị
     * được số lịch tương lai bị ảnh hưởng để Admin quyết định phương án.
     */
    public function specialists(Request $request): View
    {
        $query = SpecialistProfile::query()
            ->with(['user', 'services', 'workingSchedules'])
            ->withCount([
                'appointments',
                'appointments as future_appointments_count' => fn ($sub) => $sub->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])->where('start_at', '>', now()),
                'appointments as paid_future_count' => fn ($sub) => $sub->where('payment_status', 'PAID')->where('start_at', '>', now())->whereNotIn('status', ['CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC']),
            ])
            ->orderBy('display_order')
            ->orderBy('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($sub) use ($term): void {
                $sub->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('expertise', 'like', "%{$term}%");
            });
        }

        $specialists = $query->paginate(15)->withQueryString();
        $services = Service::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']);

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

    public function toggleSpecialist(Request $request, SpecialistProfile $specialistProfile, AttentionService $attention): RedirectResponse
    {
        $newStatus = $specialistProfile->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        if ($newStatus === 'ACTIVE' && ($specialistProfile->services()->count() === 0 || $specialistProfile->workingSchedules()->where('is_active', true)->count() === 0)) {
            return back()->withErrors(['specialist' => 'Chuyên viên cần có ít nhất một dịch vụ và một ca làm việc trước khi kích hoạt.']);
        }

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;

        $old = ['status' => $specialistProfile->status];
        $specialistProfile->update(['status' => $newStatus]);
        if ($newStatus === 'INACTIVE') {
            $attention->flag(
                $specialistProfile->appointments()->holdingSlot()->where('start_at', '>', now()),
                AttentionService::REASON_SPECIALIST_INACTIVE
            );
        }
        $this->audit('specialist.status_changed', 'specialist_profiles', $specialistProfile->id, $old, ['status' => $newStatus, 'reason' => $reason]);

        return back()->with('success', 'Đã cập nhật trạng thái chuyên viên.');
    }

    public function services(Request $request): View
    {
        $query = Service::query()->with('category')->orderBy('display_order')->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"));
        }

        $services = $query->paginate(20)->withQueryString();

        $categories = ServiceCategory::query()->where('is_active', true)->orderBy('display_order')->get();
        $allCategories = ServiceCategory::query()->orderBy('display_order')->get();

        // Thống kê số lịch đã đặt theo gói — để Admin thấy ảnh hưởng trước khi ngừng bán.
        $usage = Appointment::query()
            ->selectRaw('service_id, COUNT(*) AS total, SUM(CASE WHEN payment_status = "PAID" THEN price ELSE 0 END) AS collected')
            ->groupBy('service_id')
            ->pluck('total', 'service_id');

        $categoryUsage = ServiceCategory::query()
            ->withCount('services')
            ->withCount(['services as appointments_count' => fn ($sub) => $sub->join('appointments', 'appointments.service_id', '=', 'services.id')])
            ->orderBy('display_order')
            ->get();

        return view('admin.services', compact('services', 'categories', 'allCategories', 'usage', 'categoryUsage'));
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
        $financial = $analytics->financials($period);
        $trend = $analytics->trend($period);
        $dailyRevenue = $analytics->dailyRevenue($period);
        $revenueByService = $analytics->revenueByService($period);
        $paymentMethods = $analytics->paymentMethodBreakdown($period);

        // Yêu cầu 1: lưu lượng theo thời gian — theo ngày và theo khung giờ trong ngày.
        $patientsByDay = $traffic->dailyPatients($from, $to);
        $hourlyTraffic = $traffic->byHourOfDay($to->toDateString());
        $peakHour = $hourlyTraffic->sortByDesc('total')->first();

        return view('admin.reports', compact(
            'from', 'to', 'byStatus', 'byService', 'byDay', 'financial', 'trend',
            'dailyRevenue', 'revenueByService', 'paymentMethods',
            'patientsByDay', 'hourlyTraffic', 'peakHour'
        ));
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
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $logs = $query->paginate(30)->withQueryString();

        $actors = User::query()->whereNotNull('id')
            ->whereHas('auditLogs')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $actionCounts = AuditLog::query()
            ->selectRaw('action, COUNT(*) AS total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(12)
            ->pluck('total', 'action');

        return view('admin.audit-logs', compact('logs', 'actors', 'actionCounts'));
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

    /**
     * Ngày nghỉ phòng khám & ngoại lệ lịch — đặc tả §3.1.
     *
     * Màn hình cho phép Admin xem trước số lịch bị ảnh hưởng trước khi áp dụng,
     * đồng thời tách riêng nghỉ toàn phòng khám (HOLIDAY) và nghỉ riêng từng chuyên viên.
     */
    public function schedule(Request $request): View
    {
        $query = ScheduleException::query()->with(['specialistProfile.user', 'creator'])->latest('start_date')->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }
        if ($request->filled('scope')) {
            $query->when(
                $request->string('scope')->toString() === 'clinic',
                fn ($sub) => $sub->whereNull('specialist_profile_id'),
                fn ($sub) => $sub->whereNotNull('specialist_profile_id')
            );
        }

        $exceptions = $query->paginate(20)->withQueryString();

        $specialists = SpecialistProfile::query()->with('user')->where('status', 'ACTIVE')->orderBy('display_order')->get();

        // Ngoại lệ đang chờ phòng khám đánh giá (quy trình 4 bước của chuyên viên).
        $pendingReview = ScheduleException::query()
            ->with(['specialistProfile.user'])
            ->pendingReview()
            ->orderBy('created_at')
            ->get();

        // Lịch sắp tới đang chờ xác nhận — dùng làm mẫu xem trước ảnh hưởng.
        $upcoming = Appointment::query()
            ->with(['customer', 'service', 'specialistProfile.user'])
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(12)
            ->get();

        $typeCounts = ScheduleException::query()->selectRaw('type, COUNT(*) AS total')->groupBy('type')->pluck('total', 'type');

        return view('admin.schedule', compact('exceptions', 'specialists', 'pendingReview', 'upcoming', 'typeCounts'));
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
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }
        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function ($sub) use ($term): void {
                $sub->where('appointment_code', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('service', fn ($service) => $service->where('name', 'like', "%{$term}%"));
            });
        }
        if ($request->boolean('attention')) {
            $query->where('needs_attention', true);
        }
        if ($request->boolean('unpaid_cash')) {
            $query->where('status', 'COMPLETED')
                ->where('payment_method', 'CASH')
                ->where('payment_status', 'UNPAID');
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

    /**
     * Hàng đợi kiểm duyệt đánh giá (đặc tả §4.1–4.2).
     *
     * Đánh giá tiêu cực không tự động bị coi là vi phạm — Admin phải chọn
     * giữ nguyên, ẩn hoặc chuyển cấp xử lý và ghi lại lý do khi ẩn.
     */
    public function reviews(Request $request): View
    {
        $query = Review::query()->with(['appointment', 'customer', 'specialistProfile.user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        $reviews = $query->paginate(20)->withQueryString();

        $statusCounts = Review::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return view('admin.reviews', compact('reviews', 'statusCounts'));
    }

    public function moderateReview(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['VISIBLE', 'HIDDEN', 'RESTORED', 'PENDING_REVIEW'])],
            'reason' => ['required_if:status,HIDDEN', 'nullable', 'string', 'max:500'],
        ]);

        $old = ['status' => $review->status];

        $review->update([
            'status' => $data['status'],
            'moderation_reason' => $data['reason'] ?? null,
            'moderated_by' => auth()->id(),
            'moderated_at' => now(),
        ]);

        $this->audit('review.moderated', 'reviews', $review->id, $old, [
            'status' => $review->status,
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái đánh giá và lưu lịch sử xử lý.');
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

        $grouped = $settings->groupBy(fn (SystemSetting $setting): string => (string) ($setting->group ?: 'khac'));

        $groupLabels = [
            'booking' => 'Đặt lịch',
            'appointment' => 'Vận hành lịch hẹn',
            'schedule' => 'Quy tắc lịch',
            'policy' => 'Chính sách đổi/hủy & hoàn tiền',
            'review' => 'Đánh giá',
            'authentication' => 'Xác thực',
            'notification' => 'Thông báo',
            'khac' => 'Khác',
        ];

        return view('admin.settings', compact('settings', 'grouped', 'groupLabels'));
    }

    /**
     * Trang chẩn đoán cấu hình gửi email — quản trị viên kiểm tra ngay được
     * hệ thống có gửi được email cho khách hay không.
     */
    public function mailSettings(MailHealthService $mailHealth): View
    {
        return view('admin.mail-settings', [
            'configuration' => $mailHealth->inspectConfiguration(),
            'fromAddress' => (string) config('mail.from.address'),
            'fromName' => (string) config('mail.from.name'),
        ]);
    }

    /**
     * Gửi email thử tới một địa chỉ để kiểm tra kết nối thật.
     */
    public function sendTestMail(Request $request, MailHealthService $mailHealth): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $result = $mailHealth->verifyConnection($data['email']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:500'],
        ]);

        $changed = [];
        $previous = [];

        foreach ($data['settings'] as $key => $value) {
            $setting = SystemSetting::query()->find($key);
            if (! $setting) {
                continue;
            }
            if ((string) $setting->value !== (string) $value) {
                $previous[$key] = $setting->value;
                $changed[$key] = $value;
            }
            $setting->update(['value' => $value]);
        }

        $this->audit('settings.updated', 'system_settings', 0, $previous ?: null, $changed ?: null);

        return back()->with('success', $changed
            ? 'Đã cập nhật '.count($changed).' tham số vận hành.'
            : 'Không có tham số nào thay đổi.');
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
        $type = $request->string('type')->toString() === 'revenue' ? 'revenue' : 'appointments';

        if ($type === 'revenue') {
            return $this->exportRevenueCsv($period, $analytics);
        }

        $rows = Appointment::query()
            ->whereBetween('start_at', [$period['from'], $period['to']])
            ->selectRaw('DATE(start_at) AS day, status, COUNT(*) AS total, SUM(CASE WHEN payment_status IN ("PAID", "REFUND_PENDING", "REFUNDED") THEN price ELSE 0 END) AS collected')
            ->groupBy('day', 'status')
            ->orderBy('day')
            ->get();

        // Xuất dữ liệu là thao tác nhạy cảm — đặc tả §7 yêu cầu ghi lại nhật ký.
        $this->audit('report.exported', 'reports', 0, null, [
            'type' => $type,
            'from' => $period['from']->toDateString(),
            'to' => $period['to']->toDateString(),
            'rows' => $rows->count(),
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Ngày', 'Trạng thái', 'Số lịch', 'Đã thu']);
            foreach ($rows as $row) {
                fputcsv($output, [$this->csvSafe($row->day), $this->csvSafe($row->status), $row->total, $row->collected]);
            }
            fclose($output);
        }, 'mind-guide-lich-hen-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Xuất CSV dòng tiền — dùng đúng cùng nguồn số liệu với biểu đồ trên màn hình
     * (gom theo ngày thanh toán, không phải ngày diễn ra lịch).
     *
     * @param  array{from: CarbonImmutable, to: CarbonImmutable}  $period
     */
    private function exportRevenueCsv(array $period, AdminAnalyticsService $analytics): StreamedResponse
    {
        $series = $analytics->dailyRevenue($period);
        $methods = $analytics->paymentMethodBreakdown($period);

        $this->audit('report.exported', 'reports', 0, null, [
            'type' => 'revenue',
            'from' => $period['from']->toDateString(),
            'to' => $period['to']->toDateString(),
            'rows' => $series->count(),
        ]);

        return response()->streamDownload(function () use ($series, $methods): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Ngày', 'Tiền thực thu', 'Tiền đã hoàn', 'Dòng tiền thuần']);
            foreach ($series as $row) {
                fputcsv($output, [$this->csvSafe($row['day']), $row['collected'], $row['refunded'], $row['net']]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Cơ cấu tiền thực thu theo phương thức']);
            fputcsv($output, ['Phương thức', 'Số giao dịch', 'Tổng tiền']);
            foreach ($methods as $method) {
                fputcsv($output, [$this->csvSafe($method['label']), $method['count'], $method['total']]);
            }
            fclose($output);
        }, 'mind-guide-dong-tien-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
