<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    public const REVIEW_PENDING = 'PENDING_REVIEW';

    public const REVIEW_APPROVED = 'APPROVED';

    protected $fillable = [
        'specialist_profile_id', 'type', 'start_date', 'end_date', 'start_time', 'end_time',
        'reason', 'is_emergency', 'review_status', 'affected_appointments_count',
        'resolution_note', 'reviewed_at', 'reviewed_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_emergency' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Ngoại lệ còn chờ Lễ tân điều phối các lịch hẹn bị ảnh hưởng.
     *
     * @param  Builder<ScheduleException>  $query
     * @return Builder<ScheduleException>
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_PENDING);
    }

    public function needsReview(): bool
    {
        return $this->review_status === self::REVIEW_PENDING;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'DAY_OFF' => 'Nghỉ cả ngày',
            'TIME_BLOCK' => 'Chặn một khoảng giờ',
            'HOLIDAY' => 'Ngày lễ của phòng khám',
            default => $this->type,
        };
    }

    public function reviewStatusLabel(): string
    {
        return match ($this->review_status) {
            self::REVIEW_PENDING => 'Chờ Lễ tân điều phối',
            self::REVIEW_APPROVED => 'Đã ghi nhận',
            default => (string) $this->review_status,
        };
    }
}
