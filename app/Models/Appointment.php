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
        'start_at', 'end_at', 'duration_minutes', 'buffer_minutes', 'price',
        'status', 'payment_status', 'payment_method', 'source', 'customer_note',
        'created_by', 'slot_lock_key', 'needs_attention', 'confirmed_at', 'started_at', 'completed_at',
        'cancelled_at', 'cancel_reason',
        'checked_in_at', 'checked_in_by', 'collected_at', 'collected_by', 'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime', 'end_at' => 'datetime', 'price' => 'decimal:0',
            'customer_note' => 'encrypted', 'needs_attention' => 'boolean',
            'confirmed_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime', 'collected_at' => 'datetime',
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

    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS']);
    }
}
