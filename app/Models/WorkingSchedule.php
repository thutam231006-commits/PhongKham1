<?php

namespace App\Models;

use Database\Factories\WorkingScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingSchedule extends Model
{
    /** @use HasFactory<WorkingScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'specialist_profile_id', 'day_of_week', 'start_time', 'end_time',
        'break_start', 'break_end', 'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }
}
