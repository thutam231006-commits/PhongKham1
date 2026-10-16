<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'appointment_id', 'from_status', 'to_status', 'action', 'actor_id', 'actor_role', 'reason',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
