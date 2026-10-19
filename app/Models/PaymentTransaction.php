<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = ['appointment_id', 'provider', 'external_id', 'transfer_type', 'amount', 'content', 'status', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:0', 'payload' => 'encrypted:array', 'processed_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
