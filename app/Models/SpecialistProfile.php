<?php

namespace App\Models;

use Database\Factories\SpecialistProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialistProfile extends Model
{
    /** @use HasFactory<SpecialistProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'avatar_url', 'title', 'expertise', 'credentials', 'bio', 'years_of_experience',
        'completed_appointments', 'average_rating', 'consultation_fee', 'status', 'display_order',
    ];

    protected function casts(): array
    {
        return ['average_rating' => 'decimal:1', 'consultation_fee' => 'decimal:0'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'specialist_services');
    }

    public function workingSchedules(): HasMany
    {
        return $this->hasMany(WorkingSchedule::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
