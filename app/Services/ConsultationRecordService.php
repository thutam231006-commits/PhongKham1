<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ConsultationRecord;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * ConsultationRecordService — FR-CONSULT-001…003 (mục 29 SRS)
 *
 * Gom toàn bộ nghiệp vụ hồ sơ tư vấn vào một nơi để web và API cùng dùng chung
 * quy tắc: mã hóa ở mức trường, một record cho mỗi lịch hẹn, chống ghi đè
 * chồng (version) và ghi audit không bao giờ chứa nội dung (BR-049, BR-053).
 */
class ConsultationRecordService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Tạo hoặc cập nhật hồ sơ tư vấn của một lịch hẹn.
     *
     * @param  array{summary: string, follow_up_plan?: string|null, follow_up_needed: bool, customer_summary?: string|null, customer_recommendation?: string|null, version?: int|null}  $data
     *
     * @throws HttpException 409 khi bản ghi đã bị sửa ở nơi khác
     */
    public function upsert(Appointment $appointment, array $data, ?User $actor = null): ConsultationRecord
    {
        return DB::transaction(function () use ($appointment, $data, $actor): ConsultationRecord {
            $record = ConsultationRecord::query()
                ->where('appointment_id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if ($record && isset($data['version']) && (int) $data['version'] !== $record->version) {
                abort(409, 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại.');
            }

            $attributes = [
                'summary' => $data['summary'],
                'follow_up_plan' => $data['follow_up_plan'] ?? null,
                'follow_up_needed' => (bool) $data['follow_up_needed'],
                'customer_summary' => $data['customer_summary'] ?? null,
                'customer_recommendation' => $data['customer_recommendation'] ?? null,
            ];

            if ($record) {
                $record->update([...$attributes, 'version' => $record->version + 1]);
            } else {
                $record = ConsultationRecord::query()->create([
                    'appointment_id' => $appointment->id,
                    'specialist_profile_id' => $appointment->specialist_profile_id,
                    ...$attributes,
                    'version' => 1,
                ]);
            }

            // Fail-closed: audit nằm trong cùng giao dịch, nếu ghi audit lỗi
            // thì toàn bộ thao tác ghi bị rollback (FR-CONSULT-003).
            $this->audit->record(
                'consultation.write',
                'consultation_records',
                $record->id,
                new: ['appointment_id' => $appointment->id, 'fields' => array_keys($attributes)],
                actor: $actor,
            );

            return $record;
        });
    }

    /**
     * Ghi audit cho một lần đọc hồ sơ (FR-CONSULT-002).
     *
     * Chỉ ghi tên trường và định danh — không bao giờ ghi nội dung tư vấn.
     */
    public function recordRead(?ConsultationRecord $record, Appointment $appointment, string $scope = 'detail'): void
    {
        $this->audit->record(
            'consultation.read',
            'consultation_records',
            $record?->id,
            new: ['appointment_id' => $appointment->id, 'scope' => $scope],
        );
    }

    /**
     * Ghi audit cho một lần truy cập bị từ chối.
     */
    public function recordDenied(string $action, Appointment $appointment, ?User $actor = null): void
    {
        $this->audit->record(
            'consultation.'.$action.'.denied',
            'consultation_records',
            null,
            new: ['appointment_id' => $appointment->id],
            actor: $actor,
        );
    }

    /**
     * Các hồ sơ tư vấn của một khách hàng, thuộc chuyên viên đang xem.
     *
     * Chuyên viên chỉ thấy hồ sơ của những buổi mình được phân công.
     *
     * @return Collection<int, ConsultationRecord>
     */
    public function forSpecialistCustomer(SpecialistProfile $profile, User $customer): Collection
    {
        return $this->recordsForAppointments(
            Appointment::query()
                ->where('specialist_profile_id', $profile->id)
                ->where('customer_id', $customer->id)
                ->orderBy('start_at')
                ->orderBy('id'),
        );
    }

    /**
     * Các hồ sơ tư vấn của chính khách hàng (chỉ trường đã lọc cho khách).
     *
     * Chỉ tính các buổi đã hoàn thành — khách không thấy hồ sơ của một buổi
     * đang diễn ra.
     *
     * @return Collection<int, ConsultationRecord>
     */
    public function forCustomer(User $customer): Collection
    {
        return $this->recordsForAppointments(
            Appointment::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'COMPLETED')
                ->orderByDesc('start_at')
                ->orderByDesc('id'),
        );
    }

    /**
     * Lấy các record gắn với một tập lịch hẹn, giữ đúng thứ tự đã sắp xếp.
     *
     * Truy vấn từ `appointments` (không JOIN) để không gặp nhập nhằng tên cột
     * vì `id`/`specialist_profile_id` tồn tại ở cả hai bảng.
     *
     * @param  Builder<int, Appointment>  $appointments
     * @return Collection<int, ConsultationRecord>
     */
    private function recordsForAppointments(Builder $appointments): Collection
    {
        return $appointments
            ->with(['consultationRecord', 'service', 'specialistProfile.user'])
            ->whereHas('consultationRecord')
            ->get()
            ->map(fn (Appointment $appointment): ConsultationRecord => $appointment->consultationRecord)
            ->values();
    }
}
