<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    protected $fillable = ['appointment_id', 'amount', 'percentage', 'status', 'bank_name', 'bank_account', 'account_holder', 'reference', 'processed_by', 'processed_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:0', 'bank_name' => 'encrypted', 'bank_account' => 'encrypted', 'account_holder' => 'encrypted', 'processed_at' => 'datetime'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
