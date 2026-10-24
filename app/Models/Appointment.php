<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'appointment_code', 'customer_id', 'specialist_profile_id', 'service_id',
        'rescheduled_from_id', 'reschedule_count',
        'start_at', 'end_at', 'duration_minutes', 'buffer_minutes', 'price',
        'status', 'payment_status', 'paid_at', 'refunded_at', 'refund_amount', 'refund_status', 'payment_method', 'payment_expires_at', 'reminded_at', 'reminder_24h_at', 'reminder_30m_at', 'idempotency_key', 'source', 'customer_note',
        'created_by', 'slot_lock_key', 'needs_attention', 'attention_reason', 'attention_flagged_at', 'confirmed_at', 'started_at', 'completed_at',
        'cancelled_at', 'cancel_reason',
        'checked_in_at', 'checked_in_by', 'collected_at', 'collected_by', 'payment_reference',
        'consultation_type', 'consultation_room_id', 'meeting_url',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime', 'end_at' => 'datetime', 'price' => 'decimal:0',
            'customer_note' => 'encrypted', 'needs_attention' => 'boolean', 'attention_flagged_at' => 'datetime',
            'confirmed_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime', 'collected_at' => 'datetime',
            'paid_at' => 'datetime', 'refunded_at' => 'datetime', 'refund_amount' => 'decimal:0',
            'payment_expires_at' => 'datetime',
            'reminded_at' => 'datetime',
            'reminder_24h_at' => 'datetime',
            'reminder_30m_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class);
    }

    public function consultationRecord(): HasOne
    {
        return $this->hasOne(ConsultationRecord::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(RescheduleRequest::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function consultationRoom(): BelongsTo
    {
        return $this->belongsTo(ConsultationRoom::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Các lịch còn hiệu lực (đang giữ slot) dùng cho thống kê tải lịch.
     *
     * @param  Builder<Appointment>  $query
     * @return Builder<Appointment>
     */
    public function scopeActiveSlot(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function (Builder $query): void {
                $query
                    ->where('status', '!=', 'PENDING')
                    ->orWhereNull('payment_expires_at')
                    ->orWhere('payment_expires_at', '>', now());
            });
    }

    /** Trạng thái vẫn chiếm khung giờ của chuyên viên. */
    public const ACTIVE_STATUSES = ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS'];
}
