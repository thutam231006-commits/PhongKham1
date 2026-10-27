<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRecordRequest;
use App\Http\Requests\StoreScheduleExceptionRequest;
use App\Http\Requests\StoreWorkingScheduleRequest;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use App\Services\AppointmentWorkflowService;
use App\Services\ConsultationRecordService;
use App\Services\NotificationService;
use App\Services\SpecialistScheduleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * SpecialistController — Specialist Module (Chuyên viên / Bác sĩ)
 *
 * Ba nghiệp vụ chính theo tài liệu thiết kế:
 *  1. Quản lý lịch làm việc và ngoại lệ lịch trình (ca cố định + DAY_OFF/TIME_BLOCK + Impact Preview).
 *  2. Thực hiện ca tư vấn (bắt đầu / hoàn thành / ghi nhận vắng mặt) trong một trang danh sách.
 *  3. Ghi nhận hồ sơ tư vấn chuyên môn (summary, follow_up_plan) — mã hóa, phân quyền, audit.
 *
 * Ngoài ra controller còn phục vụ hai trang công khai SCR-PUB-04/05 (index/show).
 */
class SpecialistController extends Controller
{
    /** Số phút chờ trước khi được ghi nhận khách vắng mặt. */
    private const NO_SHOW_WAIT_MINUTES = 15;

    /** Số phút được phép bắt đầu ca trước giờ hẹn. */
    private const EARLY_START_MINUTES = 15;

    /** Chuyên viên không xem dữ liệu thanh toán; chỉ xem thông tin phục vụ buổi tư vấn. */
    private const VISIBLE_STATUSES = ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'NO_SHOW'];

    // -------------------------------------------------------------------------
    // PUBLIC — SCR-PUB-04 / SCR-PUB-05
    // -------------------------------------------------------------------------

    /**
     * GET /specialists hoặc /chuyen-vien (specialists.index) — SCR-PUB-04
     */
    public function index(Request $request): View
    {
        $serviceFilter = trim((string) $request->input('service', ''));

        $specialists = $this->getSpecialists($serviceFilter);
        $services = $this->getActiveServicesList();

        return view('public.specialists', compact(
            'specialists',
            'services',
            'serviceFilter'
        ));
    }

    /**
     * GET /specialists/{specialist} (specialists.show) — SCR-PUB-05
     *
     * Hiển thị hồ sơ chi tiết chuyên viên: ảnh, họ tên, chức danh, chuyên môn,
     * giới thiệu (bio), kinh nghiệm, danh sách dịch vụ cung cấp, phân bố sao và
     * danh sách đánh giá VISIBLE (ẩn danh, không hiện tên khách - BR-047).
     * BẢO MẬT §1.7: KHÔNG hiển thị license_number, internal_note, email, SĐT.
     */
    public function show(string|int $specialist): View
    {
        $profile = SpecialistProfile::query()->where('status', 'ACTIVE')->with(['user', 'services' => fn ($query) => $query->where('status', 'ACTIVE')])->findOrFail($specialist);
        $reviewQuery = Review::query()->where('specialist_profile_id', $profile->id)->where('status', 'VISIBLE');
        $reviews = (clone $reviewQuery)->with('appointment.service')->latest()->paginate(10);
        $ratingDistribution = collect(range(1, 5))->mapWithKeys(fn (int $rating): array => [$rating => (clone $reviewQuery)->where('rating', $rating)->count()])->all();
        $specialistItem = (object) ['id' => $profile->id, 'name' => $profile->user->name, 'title' => $profile->title, 'specialization' => $profile->expertise, 'bio' => $profile->bio, 'credentials' => $profile->credentials, 'experience_years' => $profile->years_of_experience, 'average_rating' => (float) ($reviewQuery->avg('rating') ?? 0), 'visible_reviews_count' => $reviewQuery->count(), 'avatar_url' => $profile->avatar_url, 'service_ids' => $profile->services->pluck('id')->all()];
        $providedServices = $profile->services;

        return view('public.specialist-detail', [
            'specialist' => $specialistItem,
            'providedServices' => $providedServices,
            'reviews' => $reviews,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. TỔNG QUAN
    // -------------------------------------------------------------------------

    public function dashboard(): View
    {
        $profile = $this->ownedProfile();
        $today = $this->appointmentQuery($profile)
            ->whereDate('start_at', today())
            ->with(['customer', 'service', 'consultationRecord'])
            ->orderBy('start_at')
            ->get();
        $week = $this->appointmentQuery($profile)
            ->whereBetween('start_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->with(['customer', 'service'])
            ->orderBy('start_at')
            ->get();
        $needsRecord = Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->where('status', 'COMPLETED')
            ->whereDoesntHave('consultationRecord')
            ->with(['customer', 'service'])
            ->orderByDesc('start_at')
            ->limit(5)
            ->get();

        $summary = [
            'todayTotal' => $today->count(),
            'todayUpcoming' => $today->whereIn('status', ['CONFIRMED', 'IN_PROGRESS'])->count(),
            'weekTotal' => $week->count(),
            'needsRecord' => $needsRecord->count(),
        ];

        return view('specialist.dashboard', compact('profile', 'today', 'week', 'needsRecord', 'summary'));
    }

    // -------------------------------------------------------------------------
    // 2. LỊCH LÀM VIỆC + NGOẠI LỆ + IMPACT PREVIEW
    // -------------------------------------------------------------------------

    public function schedule(SpecialistScheduleService $schedules): View
    {
        $profile = $this->ownedProfile();
        $workingSchedules = $profile->workingSchedules()->orderBy('day_of_week')->orderBy('start_time')->get();
        $exceptions = $profile->scheduleExceptions()->latest('start_date')->latest('id')->get();

        $upcomingAppointments = $this->appointmentQuery($profile)
            ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
            ->where('start_at', '>=', now())
            ->with(['customer', 'service'])
            ->orderBy('start_at')
            ->limit(20)
            ->get();

        return view('specialist.schedule', [
            'profile' => $profile,
            'schedulesByDay' => $schedules->groupByWeekday($workingSchedules),
            'workingSchedules' => $workingSchedules,
            'exceptions' => $exceptions,
            'upcomingAppointments' => $upcomingAppointments,
            'pendingReviewCount' => $exceptions->where('review_status', 'PENDING_REVIEW')->count(),
        ]);
    }

    /**
     * Xem trước tác động của ngoại lệ trước khi đăng ký (Impact Preview, §2.4).
     *
     * Chỉ đọc dữ liệu — không ghi ngoại lệ, không đổi lịch hẹn.
     */
    public function previewScheduleException(StoreScheduleExceptionRequest $request, SpecialistScheduleService $schedules): View|RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validated();

        // Các trường ngày/giờ dùng để xem trước không hợp lệ thì quay về trang lịch.
        if (($data['end_date'] ?? '') < ($data['start_date'] ?? '')) {
            return back()->withInput()->with('error', 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        }

        if ($data['type'] === 'TIME_BLOCK' && (! isset($data['start_time'], $data['end_time']) || $data['end_time'] <= $data['start_time'])) {
            return back()->withInput()->with('error', 'Khoảng giờ bị chặn chưa hợp lệ.');
        }

        $impacted = $schedules->impactedAppointments(
            $profile,
            $data['type'],
            Carbon::parse($data['start_date']),
            Carbon::parse($data['end_date']),
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
        );

        $workingSchedules = $profile->workingSchedules()->orderBy('day_of_week')->orderBy('start_time')->get();
        $exceptions = $profile->scheduleExceptions()->latest('start_date')->latest('id')->get();

        return view('specialist.schedule', [
            'profile' => $profile,
            'schedulesByDay' => $schedules->groupByWeekday($workingSchedules),
            'workingSchedules' => $workingSchedules,
            'exceptions' => $exceptions,
            'upcomingAppointments' => $this->appointmentQuery($profile)
                ->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])
                ->where('start_at', '>=', now())
                ->with(['customer', 'service'])
                ->orderBy('start_at')
                ->limit(20)
                ->get(),
            'pendingReviewCount' => $exceptions->where('review_status', 'PENDING_REVIEW')->count(),
            'preview' => $data,
            'impactedAppointments' => $impacted,
        ]);
    }

    public function storeSchedule(StoreWorkingScheduleRequest $request, SpecialistScheduleService $schedules): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validated();
        $schedules->validateWindow($data);
        $schedules->assertNoOverlap($profile, $data);

        $profile->workingSchedules()->create([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Đã thêm ca tư vấn vào lịch làm việc.');
    }

    public function updateSchedule(StoreWorkingScheduleRequest $request, WorkingSchedule $workingSchedule, SpecialistScheduleService $schedules): RedirectResponse
    {
        $profile = $this->ownedProfile();
        abort_unless($workingSchedule->specialist_profile_id === $profile->id, 403);
        $data = $request->validated();
        $schedules->validateWindow($data);
        $schedules->assertNoOverlap($profile, $data, $workingSchedule->id);
        $workingSchedule->update([...$data, 'is_active' => (bool) ($data['is_active'] ?? false)]);

        return back()->with('success', 'Đã cập nhật ca tư vấn.');
    }

    public function destroySchedule(WorkingSchedule $workingSchedule): RedirectResponse
    {
        $profile = $this->ownedProfile();
        abort_unless($workingSchedule->specialist_profile_id === $profile->id, 403);
        $workingSchedule->delete();

        return back()->with('success', 'Đã xóa ca tư vấn khỏi lịch làm việc.');
    }

    /**
     * Lưu ngoại lệ sau khi chuyên viên đã xem trước tác động.
     *
     * Lịch hẹn bị ảnh hưởng KHÔNG bị hủy hay đổi chuyên viên — chỉ được gắn cờ
     * để Lễ tân điều phối (§2.4, §6).
     */
    public function storeScheduleException(StoreScheduleExceptionRequest $request, SpecialistScheduleService $schedules): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validated();
        $exception = $schedules->createException($profile, $data);

        if ($exception->affected_appointments_count > 0) {
            return back()->with(
                'success',
                'Đã ghi nhận ngoại lệ. '.$exception->affected_appointments_count.' lịch hẹn bị ảnh hưởng đã được chuyển Lễ tân điều phối.'
            );
        }

        return back()->with('success', 'Đã ghi nhận ngoại lệ lịch trình. Không có lịch hẹn nào bị ảnh hưởng.');
    }

    public function destroyScheduleException(int $exception): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $record = $profile->scheduleExceptions()->whereKey($exception)->firstOrFail();

        if ($record->review_status === 'PENDING_REVIEW' && $record->affected_appointments_count > 0) {
            return back()->with('error', 'Ngoại lệ đang chờ Lễ tân điều phối nên chưa thể hủy. Vui lòng liên hệ Lễ tân.');
        }

        $record->delete();

        return back()->with('success', 'Đã hủy ngoại lệ lịch trình.');
    }

    // -------------------------------------------------------------------------
    // 3. THỰC HIỆN CA TƯ VẤN
    // -------------------------------------------------------------------------

    /**
     * GET /specialist/appointments — một trang duy nhất cho mọi thao tác ca tư vấn.
     */
    public function appointments(Request $request): View
    {
        $profile = $this->ownedProfile();
        $selectedDate = $request->filled('date') ? Carbon::parse($request->date('date')) : today();

        $base = $this->appointmentQuery($profile)->whereDate('start_at', $selectedDate);

        $appointments = (clone $base)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->with(['customer', 'service', 'consultationRecord', 'consultationRoom'])
            ->orderBy('start_at')
            ->get();

        $summary = [
            'total' => (clone $base)->count(),
            'waiting' => (clone $base)->where('status', 'CONFIRMED')->count(),
            'inProgress' => (clone $base)->where('status', 'IN_PROGRESS')->count(),
            'completed' => (clone $base)->where('status', 'COMPLETED')->count(),
            'noShow' => (clone $base)->where('status', 'NO_SHOW')->count(),
        ];

        return view('specialist.appointments', compact('profile', 'appointments', 'selectedDate', 'summary'));
    }

    public function appointmentDetail(int $id): View
    {
        $appointment = $this->ownedAppointment($id)->load(['customer', 'service', 'statusHistory', 'consultationRecord', 'consultationRoom']);

        return view('specialist.appointment-detail', compact('appointment'));
    }

    /**
     * Bắt đầu ca: chỉ khi lịch thuộc chuyên viên và đang CONFIRMED (khách đã check-in
     * hoặc đã xác nhận), trong khung giờ cho phép.
     */
    public function startAppointment(int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        $allowedStartAt = $appointment->start_at->copy()->subMinutes(self::EARLY_START_MINUTES);

        if ($appointment->status !== 'CONFIRMED') {
            return $this->backToAppointment($appointment, 'error', 'Lịch hẹn không ở trạng thái có thể bắt đầu.');
        }

        if (now()->lt($allowedStartAt)) {
            return $this->backToAppointment($appointment, 'error', 'Chưa đến giờ bắt đầu. Bạn có thể bắt đầu từ '.$allowedStartAt->format('d/m/Y H:i').'.');
        }

        if (now()->gt($appointment->start_at->copy()->endOfDay())) {
            return $this->backToAppointment($appointment, 'error', 'Ca tư vấn đã qua ngày làm việc. Hãy phối hợp với Lễ tân để xử lý ngoại lệ.');
        }

        $isLateStart = now()->gt($appointment->end_at);

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'IN_PROGRESS', 'started_at' => now()]);
            $appointment->statusHistory()->create([
                'from_status' => 'CONFIRMED', 'to_status' => 'IN_PROGRESS', 'action' => 'start',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        $message = $isLateStart
            ? 'Đã bắt đầu ca tư vấn (khởi động muộn so với giờ hẹn). Bạn có thể ghi hồ sơ khi buổi tư vấn kết thúc.'
            : 'Đã bắt đầu ca tư vấn. Bạn có thể ghi hồ sơ khi buổi tư vấn kết thúc.';

        return $this->backToAppointment($appointment, 'success', $message);
    }

    /**
     * Hoàn thành ca: lưu thời điểm kết thúc rồi đưa thẳng tới form ghi nhận hồ sơ.
     */
    public function completeAppointment(int $id, NotificationService $notifications): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);

        if ($appointment->status !== 'IN_PROGRESS') {
            return $this->backToAppointment($appointment, 'error', 'Chỉ có thể hoàn thành buổi tư vấn đang diễn ra.');
        }

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'COMPLETED', 'completed_at' => now(), 'slot_lock_key' => null]);
            $appointment->statusHistory()->create([
                'from_status' => 'IN_PROGRESS', 'to_status' => 'COMPLETED', 'action' => 'complete',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        $notifications->send($appointment->customer, 'Buổi tư vấn đã hoàn thành', 'Bạn có thể gửi đánh giá cho lịch '.$appointment->appointment_code.' trong 30 ngày.', 'appointment.completed', route('customer.review', ['appointment_id' => $appointment->id]), $appointment);

        return redirect()
            ->route('specialist.consultation-note', $appointment->id)
            ->with('success', 'Đã hoàn thành ca tư vấn. Hãy ghi nhận hồ sơ chuyên môn cho buổi này.');
    }

    /**
     * Gửi thông tin buổi tư vấn cho khách hàng — chỉ khách nhận.
     *
     * Chuyên viên dùng khi cần gửi lại đầy đủ thông tin buổi hẹn (thời gian,
     * địa điểm, chuyên viên, lưu ý chuẩn bị) qua email + thông báo trong hệ thống.
     */
    public function sendInformation(Request $request, int $id, AppointmentWorkflowService $workflow): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $workflow->sendSessionInformation($appointment, $request->user(), $data['note'] ?? null);

        return $this->backToAppointment($appointment, $result['sent'] ? 'success' : 'error', $result['message']);
    }

    public function noShowAppointment(int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);

        if (! in_array($appointment->status, ['CONFIRMED'], true)) {
            return $this->backToAppointment($appointment, 'error', 'Chỉ ghi nhận vắng mặt cho lịch đã xác nhận.');
        }

        if (now()->lt($appointment->start_at->copy()->addMinutes(self::NO_SHOW_WAIT_MINUTES))) {
            return $this->backToAppointment($appointment, 'error', 'Chưa hết thời gian chờ '.self::NO_SHOW_WAIT_MINUTES.' phút kể từ giờ hẹn.');
        }

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'NO_SHOW', 'slot_lock_key' => null]);
            $appointment->statusHistory()->create([
                'from_status' => 'CONFIRMED', 'to_status' => 'NO_SHOW', 'action' => 'no_show',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        return $this->backToAppointment($appointment, 'success', 'Đã ghi nhận khách vắng mặt cho ca này.');
    }

    // -------------------------------------------------------------------------
    // 4. HỒ SƠ TƯ VẤN CHUYÊN MÔN
    // -------------------------------------------------------------------------

    /**
     * Hồ sơ khách hàng mà chuyên viên đang phụ trách.
     *
     * Chỉ hiển thị các buổi tư vấn mà chính chuyên viên này phụ trách; nội dung
     * chuyên môn được mã hóa và mọi lần xem đều được ghi audit (BR-049).
     */
    public function customerProfile(User $customer, ConsultationRecordService $records): View
    {
        abort_unless(strtolower((string) $customer->role) === 'customer', 404);

        $profile = $this->ownedProfile();
        $appointments = Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->where('customer_id', $customer->id)
            ->with(['service', 'consultationRecord'])
            ->orderByDesc('start_at')
            ->get();

        $consultations = $records->forSpecialistCustomer($profile, $customer);

        foreach ($consultations as $record) {
            $records->recordRead($record, $record->appointment, 'customer_profile');
        }

        return view('specialist.customer-profile', compact('customer', 'appointments', 'consultations'));
    }

    public function consultationNote(int $id, ConsultationRecordService $records): View
    {
        $appointment = $this->ownedAppointment($id)->load(['consultationRecord', 'customer', 'service']);
        abort_unless(in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true), 422, 'Chỉ có thể ghi hồ sơ khi buổi tư vấn đang diễn ra hoặc đã hoàn thành.');
        $records->recordRead($appointment->consultationRecord, $appointment);

        return view('specialist.consultation-note', compact('appointment'));
    }

    public function storeConsultationNote(StoreConsultationRecordRequest $request, int $id, ConsultationRecordService $records): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);

        if (! in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true)) {
            return back()->withInput()->with('error', 'Chỉ có thể lưu hồ sơ khi buổi tư vấn đang diễn ra hoặc đã hoàn thành.');
        }

        $records->upsert($appointment, $request->payload(), $request->user());

        return redirect()
            ->route('specialist.consultation-note', $appointment->id)
            ->with('success', 'Hồ sơ buổi tư vấn đã được lưu an toàn.');
    }

    // -------------------------------------------------------------------------
    // 5. CÁ NHÂN
    // -------------------------------------------------------------------------

    public function profile(): View
    {
        $profile = $this->ownedProfile()->load(['user', 'services']);

        return view('specialist.profile', compact('profile'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        unset($data['avatar']);
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('specialists', 'public');
            $data['avatar_url'] = Storage::url($path);
        }
        $profile->update($data);

        return back()->with('success', 'Hồ sơ chuyên viên đã được cập nhật.');
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    private function backToAppointment(Appointment $appointment, string $key, string $message): RedirectResponse
    {
        $target = request()->headers->get('referer');

        if (is_string($target) && str_contains($target, '/specialist/appointments')) {
            return back()->with($key, $message);
        }

        return redirect()->route('specialist.appointments.detail', $appointment->id)->with($key, $message);
    }

    /**
     * Truy vấn lịch hẹn thuộc chuyên viên, loại bỏ trạng thái đã hủy.
     *
     * @return Builder<Appointment>
     */
    private function appointmentQuery(SpecialistProfile $profile): Builder
    {
        return Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->whereIn('status', self::VISIBLE_STATUSES);
    }

    private function ownedProfile(): SpecialistProfile
    {
        return SpecialistProfile::query()->where('user_id', auth()->id())->firstOrFail();
    }

    private function ownedAppointment(int $id): Appointment
    {
        return Appointment::query()
            ->whereKey($id)
            ->where('specialist_profile_id', $this->ownedProfile()->id)
            ->firstOrFail();
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    private function getSpecialists(string $serviceFilter): Collection
    {
        $query = SpecialistProfile::query()
            ->where('status', 'ACTIVE')
            ->with(['user', 'services'])
            ->withCount(['reviews as visible_reviews_count' => fn ($review) => $review->where('status', 'VISIBLE')])
            ->withAvg(['reviews as visible_reviews_average_rating' => fn ($review) => $review->where('status', 'VISIBLE')], 'rating')
            ->orderByDesc('visible_reviews_average_rating')
            ->orderBy('display_order');

        if ($serviceFilter !== '') {
            $query->whereHas('services', fn ($service) => $service->where('services.id', $serviceFilter));
        }

        return $query->get()->map(fn (SpecialistProfile $profile): object => (object) [
            'id' => $profile->id,
            'name' => $profile->user->name,
            'title' => $profile->title,
            'specialization' => $profile->expertise,
            'experience_years' => $profile->years_of_experience,
            'average_rating' => (float) ($profile->visible_reviews_average_rating ?? 0),
            'visible_reviews_count' => $profile->visible_reviews_count,
            'avatar_url' => $profile->avatar_url,
            'service_ids' => $profile->services->pluck('id')->all(),
        ]);
    }

    private function getActiveServicesList(): Collection
    {
        return Service::query()->active()->orderBy('display_order')->get();
    }
}
