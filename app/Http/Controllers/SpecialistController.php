<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRecordRequest;
use App\Http\Requests\StoreWorkingScheduleRequest;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Models\WorkingSchedule;
use App\Services\AttentionService;
use App\Services\ConsultationRecordService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * SpecialistController — SCR-PUB-04 & SCR-PUB-05
 *
 * Quản lý hiển thị danh sách chuyên viên công khai (SCR-PUB-04):
 *  - Lọc theo dịch vụ
 *  - Đánh giá sao chỉ tính từ đánh giá VISIBLE kèm số lượt
 *  - Bảo mật: Không hiển thị license_number, internal_note, email, SĐT (§1.7)
 */
class SpecialistController extends Controller
{
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

    public function dashboard(): View
    {
        $profile = $this->ownedProfile();
        $today = Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->whereDate('start_at', today())
            ->with(['customer', 'service'])
            ->orderBy('start_at')
            ->get();
        $week = Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->whereBetween('start_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->with(['customer', 'service'])
            ->orderBy('start_at')
            ->get();

        return view('specialist.dashboard', compact('profile', 'today', 'week'));
    }

    public function schedule(): View
    {
        $profile = $this->ownedProfile();
        $schedules = $profile->workingSchedules()->orderBy('day_of_week')->orderBy('start_time')->get();
        $exceptions = $profile->scheduleExceptions()->latest('start_date')->get();

        return view('specialist.schedule', compact('profile', 'schedules', 'exceptions'));
    }

    public function storeScheduleException(Request $request, AttentionService $attention): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validate(['type' => ['required', 'in:DAY_OFF,TIME_BLOCK'], 'start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date'], 'start_time' => ['nullable', 'date_format:H:i'], 'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'], 'reason' => ['required', 'string', 'max:255'], 'is_emergency' => ['nullable', 'boolean']]);
        $exception = $profile->scheduleExceptions()->create([...$data, 'created_by' => auth()->id(), 'is_emergency' => (bool) ($data['is_emergency'] ?? false)]);
        $attention->flag(
            Appointment::query()->where('specialist_profile_id', $profile->id)->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'])->whereBetween('start_at', [$exception->start_date->startOfDay(), $exception->end_date->endOfDay()]),
            AttentionService::REASON_SCHEDULE_EXCEPTION
        );

        return back()->with('success', 'Đã lưu ngày nghỉ/chặn giờ và đánh dấu lịch bị ảnh hưởng.');
    }

    public function storeSchedule(StoreWorkingScheduleRequest $request): RedirectResponse
    {
        $profile = $this->ownedProfile();
        $data = $request->validated();
        $this->validateScheduleWindow($data);
        $this->assertNoScheduleOverlap($profile, $data);

        $profile->workingSchedules()->create([
            ...$data,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Đã thêm ca tư vấn vào lịch làm việc.');
    }

    public function updateSchedule(StoreWorkingScheduleRequest $request, WorkingSchedule $workingSchedule): RedirectResponse
    {
        $profile = $this->ownedProfile();
        abort_unless($workingSchedule->specialist_profile_id === $profile->id, 403);
        $data = $request->validated();
        $this->validateScheduleWindow($data);
        $this->assertNoScheduleOverlap($profile, $data, $workingSchedule->id);
        $workingSchedule->update([...$data, 'is_active' => (bool) ($data['is_active'] ?? false)]);

        return back()->with('success', 'Đã cập nhật ca tư vấn.');
    }

    public function appointments(Request $request): View
    {
        $profile = $this->ownedProfile();
        $query = Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->with(['customer', 'service'])
            ->orderBy('start_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('start_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('start_at', '<=', $request->date('to'));
        }

        $appointments = $query->paginate(20)->withQueryString();

        return view('specialist.appointments', compact('profile', 'appointments'));
    }

    public function appointmentDetail(int $id): View
    {
        $appointment = $this->ownedAppointment($id)->load(['customer', 'service', 'statusHistory', 'consultationRecord', 'consultationRoom']);

        return view('specialist.appointment-detail', compact('appointment'));
    }

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

    public function startAppointment(int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        if ($appointment->status !== 'CONFIRMED') {
            return redirect()
                ->route('specialist.appointments.detail', $appointment->id)
                ->with('error', 'Lịch hẹn không ở trạng thái có thể bắt đầu.');
        }

        $now = now();
        $allowedStartAt = $appointment->start_at->copy()->subMinutes(15);

        if ($now->lt($allowedStartAt)) {
            return redirect()
                ->route('specialist.appointments.detail', $appointment->id)
                ->with('error', 'Chưa đến giờ bắt đầu. Bạn có thể bắt đầu từ '.$allowedStartAt->format('d/m/Y H:i').'.');
        }

        if ($now->gt($appointment->end_at)) {
            return redirect()
                ->route('specialist.appointments.detail', $appointment->id)
                ->with('error', 'Khung giờ tư vấn đã kết thúc. Hãy kiểm tra lịch hoặc đánh dấu khách vắng mặt nếu phù hợp.');
        }

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'IN_PROGRESS', 'started_at' => now()]);
            $appointment->statusHistory()->create([
                'from_status' => 'CONFIRMED', 'to_status' => 'IN_PROGRESS', 'action' => 'start',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        return back()->with('success', 'Buổi tư vấn đã được bắt đầu.');
    }

    public function completeAppointment(int $id, NotificationService $notifications): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        abort_unless($appointment->status === 'IN_PROGRESS', 409, 'Chỉ có thể hoàn thành buổi tư vấn đang diễn ra.');

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'COMPLETED', 'completed_at' => now(), 'slot_lock_key' => null]);
            $appointment->statusHistory()->create([
                'from_status' => 'IN_PROGRESS', 'to_status' => 'COMPLETED', 'action' => 'complete',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });
        $notifications->send($appointment->customer, 'Buổi tư vấn đã hoàn thành', 'Bạn có thể gửi đánh giá cho lịch '.$appointment->appointment_code.' trong 30 ngày.', 'appointment.completed', route('customer.review', ['appointment_id' => $appointment->id]), $appointment);

        return back()->with('success', 'Buổi tư vấn đã hoàn thành.');
    }

    public function noShowAppointment(int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        abort_unless($appointment->status === 'CONFIRMED', 409, 'Lịch hẹn không ở trạng thái có thể đánh dấu vắng mặt.');
        abort_unless(now()->gte($appointment->start_at->copy()->addMinutes(15)), 422, 'Chưa hết thời gian chờ 15 phút.');

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'NO_SHOW', 'slot_lock_key' => null]);
            $appointment->statusHistory()->create([
                'from_status' => 'CONFIRMED', 'to_status' => 'NO_SHOW', 'action' => 'no_show',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        return back()->with('success', 'Đã đánh dấu khách vắng mặt.');
    }

    public function consultationNote(int $id, ConsultationRecordService $records): View
    {
        $appointment = $this->ownedAppointment($id)->load('consultationRecord');
        abort_unless(in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true), 422, 'Chỉ có thể ghi hồ sơ khi buổi tư vấn đang diễn ra hoặc đã hoàn thành.');
        $records->recordRead($appointment->consultationRecord, $appointment);

        return view('specialist.consultation-note', compact('appointment'));
    }

    public function storeConsultationNote(StoreConsultationRecordRequest $request, int $id, ConsultationRecordService $records): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        abort_unless(in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true), 422);

        $records->upsert($appointment, $request->payload(), $request->user());

        return back()->with('success', 'Hồ sơ buổi tư vấn đã được lưu an toàn.');
    }

    public function profile(): View
    {
        $profile = $this->ownedProfile()->load('user');

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

    /** @param array<string, mixed> $data */
    private function validateScheduleWindow(array $data): void
    {
        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'Giờ kết thúc phải sau giờ bắt đầu.']);
        }
        if (($data['break_start'] ?? null) || ($data['break_end'] ?? null)) {
            if (! ($data['break_start'] ?? null) || ! ($data['break_end'] ?? null)) {
                throw ValidationException::withMessages(['break_start' => 'Cần nhập đủ giờ nghỉ giữa ca.']);
            }
            if (! ($data['break_start'] > $data['start_time'] && $data['break_end'] < $data['end_time'] && $data['break_end'] > $data['break_start'])) {
                throw ValidationException::withMessages(['break_start' => 'Giờ nghỉ phải nằm trong ca làm việc.']);
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function assertNoScheduleOverlap(SpecialistProfile $profile, array $data, ?int $exceptId = null): void
    {
        $query = $profile->workingSchedules()
            ->where('day_of_week', $data['day_of_week'])
            ->where('is_active', true)
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);
        if ($exceptId) {
            $query->where($profile->getKeyName(), '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Các ca làm việc trong cùng ngày không được chồng nhau.']);
        }
    }

    /**
     * Dữ liệu mẫu đánh giá VISIBLE của chuyên viên (ẩn danh theo BR-047).
     */
    private function getSampleReviewsForSpecialist(int|string $specialistId): Collection
    {
        return collect([
            (object) [
                'id' => 101,
                'rating' => 5,
                'comment' => 'Chuyên viên rất tận tâm và lắng nghe sâu sắc. Sau 3 buổi tham vấn, tôi đã học được cách kiểm soát các cơn hoảng loạn và tìm lại sự bình yên trong tâm trí.',
                'created_at' => '12/10/2026',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
            ],
            (object) [
                'id' => 102,
                'rating' => 5,
                'comment' => 'Không gian chia sẻ mang lại cảm giác an toàn tuyệt đối. Chuyên gia không phán xét, luôn thấu cảm và đưa ra những bài tập chánh niệm rất hữu ích.',
                'created_at' => '05/10/2026',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
            ],
            (object) [
                'id' => 103,
                'rating' => 4,
                'comment' => 'Buổi tư vấn diễn ra đúng giờ, chuyên gia có chuyên môn cao và định hướng rõ ràng. Tôi cảm thấy nhẹ nhõm hơn rất nhiều.',
                'created_at' => '28/09/2026',
                'service_name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình',
            ],
        ]);
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
            ->withAvg(['reviews as average_rating' => fn ($review) => $review->where('status', 'VISIBLE')], 'rating')
            ->orderByDesc('average_rating')
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
            'average_rating' => (float) ($profile->average_rating ?? 0),
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
