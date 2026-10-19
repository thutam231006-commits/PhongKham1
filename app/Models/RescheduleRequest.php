<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    protected $fillable = ['appointment_id', 'requested_start_at', 'requested_end_at', 'status', 'reason', 'requested_by', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['requested_start_at' => 'datetime', 'requested_end_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
