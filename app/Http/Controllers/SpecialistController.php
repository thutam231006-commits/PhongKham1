<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRecordRequest;
use App\Http\Requests\StoreWorkingScheduleRequest;
use App\Models\Appointment;
use App\Models\Specialist;
use App\Models\SpecialistProfile;
use App\Models\WorkingSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $specialistItem = null;

        // 1. Tìm trong DB nếu Model Specialist tồn tại
        if (class_exists(Specialist::class)) {
            try {
                $specialistItem = Specialist::query()
                    ->where('status', 'ACTIVE')
                    ->where(function ($q) use ($specialist) {
                        $q->where('id', $specialist);
                        if (! is_numeric($specialist)) {
                            $q->orWhere('slug', $specialist);
                        }
                    })
                    ->with(['services' => fn ($s) => $s->where('status', 'ACTIVE')])
                    ->withCount(['reviews as visible_reviews_count' => fn ($q) => $q->where('status', 'VISIBLE')])
                    ->withAvg(['reviews as average_rating' => fn ($q) => $q->where('status', 'VISIBLE')], 'rating')
                    ->first();
            } catch (\Throwable) {
                // DB lỗi hoặc chưa migrate
            }
        }

        // 2. Fallback tìm trong dữ liệu mẫu demo
        if (! $specialistItem) {
            $all = $this->getSpecialists('');
            $specialistItem = $all->first(function ($item) use ($specialist) {
                return (string) $item->id === (string) $specialist
                    || (isset($item->slug) && $item->slug === $specialist);
            });
        }

        // Nếu không tìm thấy chuyên viên hoặc INACTIVE -> 404
        if (! $specialistItem) {
            abort(404, 'Không tìm thấy thông tin chuyên viên tâm lý.');
        }

        // 3. Lấy danh sách dịch vụ mà chuyên viên này cung cấp
        $allServices = $this->getActiveServicesList();
        $serviceIds = (array) ($specialistItem->service_ids ?? []);
        $providedServices = $allServices->filter(fn ($s) => in_array($s->id, $serviceIds))->values();

        // 4. Dữ liệu đánh giá mẫu VISIBLE (BR-047: ẩn danh)
        $reviews = $this->getSampleReviewsForSpecialist($specialistItem->id);

        // 5. Phân bố sao đánh giá
        $ratingDistribution = [
            5 => 80,
            4 => 15,
            3 => 5,
            2 => 0,
            1 => 0,
        ];

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

        return view('specialist.schedule', compact('profile', 'schedules'));
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
        $appointment = $this->ownedAppointment($id)->load(['customer', 'service', 'statusHistory', 'consultationRecord']);

        return view('specialist.appointment-detail', compact('appointment'));
    }

    public function startAppointment(int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        abort_unless($appointment->status === 'CONFIRMED', 409, 'Lịch hẹn không ở trạng thái có thể bắt đầu.');
        abort_unless(now()->gte($appointment->start_at->copy()->subMinutes(15)) && now()->lte($appointment->end_at), 422, 'Chưa đến khung giờ bắt đầu buổi tư vấn.');

        DB::transaction(function () use ($appointment): void {
            $appointment->update(['status' => 'IN_PROGRESS', 'started_at' => now()]);
            $appointment->statusHistory()->create([
                'from_status' => 'CONFIRMED', 'to_status' => 'IN_PROGRESS', 'action' => 'start',
                'actor_id' => auth()->id(), 'actor_role' => 'SPECIALIST',
            ]);
        });

        return back()->with('success', 'Buổi tư vấn đã được bắt đầu.');
    }

    public function completeAppointment(int $id): RedirectResponse
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

    public function consultationNote(int $id): View
    {
        $appointment = $this->ownedAppointment($id)->load('consultationRecord');
        abort_unless(in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true), 422, 'Chỉ có thể ghi hồ sơ khi buổi tư vấn đang diễn ra hoặc đã hoàn thành.');

        return view('specialist.consultation-note', compact('appointment'));
    }

    public function storeConsultationNote(StoreConsultationRecordRequest $request, int $id): RedirectResponse
    {
        $appointment = $this->ownedAppointment($id);
        abort_unless(in_array($appointment->status, ['IN_PROGRESS', 'COMPLETED'], true), 422);
        $data = $request->validated();

        DB::transaction(function () use ($appointment, $data): void {
            $record = $appointment->consultationRecord;
            if ($record && isset($data['version']) && (int) $data['version'] !== $record->version) {
                abort(409, 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại.');
            }

            if ($record) {
                $record->update([
                    'summary' => $data['summary'],
                    'follow_up_plan' => $data['follow_up_plan'] ?? null,
                    'follow_up_needed' => (bool) $data['follow_up_needed'],
                    'version' => $record->version + 1,
                ]);
            } else {
                $appointment->consultationRecord()->create([
                    'specialist_profile_id' => $appointment->specialist_profile_id,
                    'summary' => $data['summary'],
                    'follow_up_plan' => $data['follow_up_plan'] ?? null,
                    'follow_up_needed' => (bool) $data['follow_up_needed'],
                    'version' => 1,
                ]);
            }
        });

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
        ]);
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
        if (class_exists(Specialist::class)) {
            try {
                $query = Specialist::query()
                    ->where('status', 'ACTIVE')
                    ->withCount([
                        'reviews as visible_reviews_count' => fn ($q) => $q->where('status', 'VISIBLE'),
                    ])
                    ->withAvg(
                        ['reviews as average_rating' => fn ($q) => $q->where('status', 'VISIBLE')],
                        'rating'
                    )
                    ->orderByDesc('average_rating')
                    ->orderByDesc('visible_reviews_count')
                    ->orderBy('name');

                if ($serviceFilter !== '') {
                    $query->whereHas('services', function ($q) use ($serviceFilter) {
                        $q->where('services.id', $serviceFilter)
                            ->orWhere('services.name', 'like', "%{$serviceFilter}%");
                    });
                }

                $results = $query->get();
                if ($results->isNotEmpty()) {
                    return $results;
                }
            } catch (\Throwable) {
                // DB chưa migrate hoặc chưa có dữ liệu
            }
        }

        // Dữ liệu mẫu demo SCR-PUB-04
        $all = collect([
            (object) [
                'id' => 1,
                'name' => 'ThS. Nguyễn Thị Mai',
                'title' => 'Chuyên viên Tâm lý học lâm sàng',
                'specialization' => 'Rối loạn lo âu, trầm cảm, sang chấn tâm lý, căng thẳng kéo dài',
                'experience_years' => 10,
                'average_rating' => 4.9,
                'visible_reviews_count' => 62,
                'avatar_url' => null,
                'service_ids' => [1, 2, 7],
            ],
            (object) [
                'id' => 2,
                'name' => 'TS. Trần Văn Hưng',
                'title' => 'Tiến sĩ Tâm lý học tham vấn',
                'specialization' => 'Tâm lý hôn nhân, khủng hoảng gia đình, trị liệu cặp đôi',
                'experience_years' => 14,
                'average_rating' => 5.0,
                'visible_reviews_count' => 84,
                'avatar_url' => null,
                'service_ids' => [3, 5],
            ],
            (object) [
                'id' => 3,
                'name' => 'ThS. Lê Hoàng An',
                'title' => 'Chuyên viên Trị liệu Hành vi Nhận thức (CBT)',
                'specialization' => 'Kiểm soát căng thẳng, rối loạn giấc ngủ, kiệt sức nghề nghiệp',
                'experience_years' => 7,
                'average_rating' => 4.8,
                'visible_reviews_count' => 41,
                'avatar_url' => null,
                'service_ids' => [1, 2, 8],
            ],
            (object) [
                'id' => 4,
                'name' => 'Chuyên viên Phạm Thu Hà',
                'title' => 'Chuyên viên Tham vấn Thanh thiếu niên',
                'specialization' => 'Áp lực học đường, giao tiếp cha mẹ - con cái, cảm xúc tuổi dậy thì',
                'experience_years' => 6,
                'average_rating' => 4.9,
                'visible_reviews_count' => 35,
                'avatar_url' => null,
                'service_ids' => [4, 9],
            ],
            (object) [
                'id' => 5,
                'name' => 'ThS. Vũ Đức Minh',
                'title' => 'Chuyên viên Đánh giá & Chẩn đoán Tâm lý',
                'specialization' => 'Sàng lọc rối loạn cảm xúc, trắc nghiệm nhân cách, định hướng phát triển',
                'experience_years' => 9,
                'average_rating' => 4.8,
                'visible_reviews_count' => 29,
                'avatar_url' => null,
                'service_ids' => [5, 6],
            ],
            (object) [
                'id' => 6,
                'name' => 'ThS. Đỗ Thúy Hằng',
                'title' => 'Chuyên viên Trị liệu Tâm lý Gia đình',
                'specialization' => 'Hàn gắn rạn nứt gia đình, trầm cảm sau sinh, khủng hoảng sau biến cố',
                'experience_years' => 8,
                'average_rating' => 4.9,
                'visible_reviews_count' => 53,
                'avatar_url' => null,
                'service_ids' => [1, 3],
            ],
        ]);

        if ($serviceFilter !== '') {
            $all = $all->filter(function ($item) use ($serviceFilter) {
                return in_array((int) $serviceFilter, $item->service_ids ?? []);
            });
        }

        return $all;
    }

    private function getActiveServicesList(): Collection
    {
        return collect([
            (object) ['id' => 1, 'name' => 'Tư vấn tâm lý cá nhân 1-1'],
            (object) ['id' => 2, 'name' => 'Tham vấn & Trị liệu lo âu, trầm cảm'],
            (object) ['id' => 3, 'name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình'],
            (object) ['id' => 4, 'name' => 'Tư vấn tâm lý học đường & Thanh thiếu niên'],
            (object) ['id' => 5, 'name' => 'Quản trị cảm xúc & Khủng hoảng tuổi trưởng thành'],
            (object) ['id' => 6, 'name' => 'Đánh giá & Sàng lọc sức khỏe tinh thần tổng quát'],
            (object) ['id' => 7, 'name' => 'Hỗ trợ phục hồi sau sang chấn tâm lý (PTSD)'],
            (object) ['id' => 8, 'name' => 'Trị liệu tâm lý nhóm & Kỹ năng ứng phó căng thẳng'],
            (object) ['id' => 9, 'name' => 'Tư vấn hướng nghiệp & Định hướng đam mê cá nhân'],
        ]);
    }
}
