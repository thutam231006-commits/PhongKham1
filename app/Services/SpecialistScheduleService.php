<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ScheduleException;
use App\Models\SpecialistProfile;
use App\Models\WorkingSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * SpecialistScheduleService — nghiệp vụ lịch làm việc của chuyên viên.
 *
 * Căn cứ: Specialist Module §2 (ca cố định + ngoại lệ DAY_OFF/TIME_BLOCK) và
 * §2.4 (Impact Preview trước khi đăng ký nghỉ, chuyển Lễ tân điều phối).
 *
 * Quy tắc quan trọng:
 *  - Không chồng chéo ca trong cùng ngày.
 *  - Giờ nghỉ phải nằm trong khung ca.
 *  - Không tự hủy/chuyển lịch đã xác nhận: chỉ gắn cờ "cần xử lý" để Lễ tân điều phối.
 */
class SpecialistScheduleService
{
    /** Các trạng thái lịch hẹn có thể bị ảnh hưởng bởi ngoại lệ. */
    private const AFFECTABLE_STATUSES = ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED'];

    public function __construct(private readonly AttentionService $attention) {}

    /**
     * Kiểm tra giờ kết thúc sau giờ bắt đầu và giờ nghỉ nằm trong ca.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function validateWindow(array $data): void
    {
        if ($data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'Giờ kết thúc phải sau giờ bắt đầu.']);
        }

        $breakStart = $data['break_start'] ?? null;
        $breakEnd = $data['break_end'] ?? null;

        if (! $breakStart && ! $breakEnd) {
            return;
        }

        if (! $breakStart || ! $breakEnd) {
            throw ValidationException::withMessages(['break_start' => 'Cần nhập đủ giờ nghỉ giữa ca.']);
        }

        if (! ($breakStart > $data['start_time'] && $breakEnd < $data['end_time'] && $breakEnd > $breakStart)) {
            throw ValidationException::withMessages(['break_start' => 'Giờ nghỉ phải nằm trong ca làm việc.']);
        }
    }

    /**
     * Chặn hai khung ca trùng nhau trong cùng một thứ.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function assertNoOverlap(SpecialistProfile $profile, array $data, ?int $exceptId = null): void
    {
        $query = $profile->workingSchedules()
            ->where('day_of_week', $data['day_of_week'])
            ->where('is_active', true)
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Các ca làm việc trong cùng ngày không được chồng nhau.']);
        }
    }

    /**
     * Danh sách lịch hẹn đang giữ slot nằm trong khoảng của ngoại lệ.
     *
     * @return Collection<int, Appointment>
     */
    public function impactedAppointments(SpecialistProfile $profile, string $type, Carbon $startDate, Carbon $endDate, ?string $startTime = null, ?string $endTime = null): Collection
    {
        return Appointment::query()
            ->where('specialist_profile_id', $profile->id)
            ->whereIn('status', self::AFFECTABLE_STATUSES)
            ->whereBetween('start_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when(
                $type === 'TIME_BLOCK' && $startTime && $endTime,
                fn (Builder $query) => $query->whereTime('start_at', '>=', $startTime)->whereTime('start_at', '<', $endTime),
            )
            ->with(['customer', 'service'])
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Tạo ngoại lệ và chuyển các lịch bị ảnh hưởng sang Lễ tân.
     *
     * Ngoại lệ ở trạng thái PENDING_REVIEW khi còn lịch bị ảnh hưởng để Lễ tân
     * chủ động liên hệ khách trước khi khoảng thời gian nghỉ có hiệu lực.
     *
     * @param  array{type: string, start_date: string, end_date: string, start_time?: string|null, end_time?: string|null, reason: string, is_emergency?: bool|null}  $data
     */
    public function createException(SpecialistProfile $profile, array $data): ScheduleException
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $impacted = $this->impactedAppointments(
            $profile,
            $data['type'],
            $startDate,
            $endDate,
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
        );

        /** @var ScheduleException $exception */
        $exception = $profile->scheduleExceptions()->create([
            'type' => $data['type'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $data['type'] === 'TIME_BLOCK' ? ($data['start_time'] ?? null) : null,
            'end_time' => $data['type'] === 'TIME_BLOCK' ? ($data['end_time'] ?? null) : null,
            'reason' => $data['reason'],
            'is_emergency' => (bool) ($data['is_emergency'] ?? false),
            'review_status' => $impacted->isEmpty() ? 'APPROVED' : 'PENDING_REVIEW',
            'affected_appointments_count' => $impacted->count(),
            'created_by' => auth()->id(),
            'reviewed_at' => $impacted->isEmpty() ? now() : null,
        ]);

        if ($impacted->isNotEmpty()) {
            $this->attention->flag(
                Appointment::query()->whereKey($impacted->modelKeys()),
                AttentionService::REASON_SCHEDULE_EXCEPTION,
            );
        }

        return $exception;
    }

    /**
     * Danh sách thứ trong tuần có ca làm việc đang áp dụng.
     *
     * @param  Collection<int, WorkingSchedule>  $schedules
     * @return array<int, Collection<int, WorkingSchedule>>
     */
    public function groupByWeekday(Collection $schedules): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(fn (int $day): array => [$day => $schedules->where('day_of_week', $day)->values()])
            ->all();
    }

    /**
     * Nhãn tiếng Việt cho thứ trong tuần.
     */
    public static function weekdayLabel(int $day): string
    {
        return [
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            7 => 'Chủ Nhật',
        ][$day] ?? '—';
    }
}
