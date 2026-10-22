<?php

namespace App\Models;

use Database\Factories\ConsultationRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationRecord extends Model
{
    /** @use HasFactory<ConsultationRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'appointment_id', 'specialist_profile_id', 'summary', 'follow_up_plan', 'follow_up_needed', 'version',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'encrypted',
            'follow_up_plan' => 'encrypted',
            'follow_up_needed' => 'boolean',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }
}
