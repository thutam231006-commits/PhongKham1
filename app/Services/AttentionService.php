<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Builder;

/**
 * AttentionService — quản lý cờ "cần xử lý" của lịch hẹn (SCR-STF-07).
 *
 * Mỗi lịch bị ảnh hưởng bởi thay đổi lịch làm việc hoặc ngừng hoạt động của
 * chuyên viên sẽ được gắn cờ kèm nguyên nhân và thời điểm gắn cờ để Staff
 * điều phối theo nhóm nguyên nhân.
 */
class AttentionService
{
    public const REASON_SPECIALIST_INACTIVE = 'Chuyên viên ngừng hoạt động';

    public const REASON_SCHEDULE_EXCEPTION = 'Chuyên viên nghỉ hoặc bị chặn giờ';

    public const REASON_SCHEDULE_SHORTENED = 'Lịch làm việc bị rút ngắn';

    public const REASON_NO_ROOM = 'Chưa xếp được phòng tư vấn';

    /**
     * Gắn cờ cho các lịch đang giữ slot bị ảnh hưởng.
     *
     * @param  Builder<Appointment>  $query
     */
    public function flag(Builder $query, string $reason): int
    {
        return $query->update([
            'needs_attention' => true,
            'attention_reason' => $reason,
            'attention_flagged_at' => now(),
        ]);
    }

    /**
     * Gắn cờ cho một lịch cụ thể.
     */
    public function flagAppointment(Appointment $appointment, string $reason): Appointment
    {
        $appointment->update([
            'needs_attention' => true,
            'attention_reason' => $reason,
            'attention_flagged_at' => now(),
        ]);

        return $appointment;
    }

    /**
     * Xóa cờ sau khi Staff/Admin đã xử lý xong lịch.
     */
    public function clear(Appointment $appointment): Appointment
    {
        $appointment->update([
            'needs_attention' => false,
            'attention_reason' => null,
            'attention_flagged_at' => null,
        ]);

        return $appointment;
    }
}
