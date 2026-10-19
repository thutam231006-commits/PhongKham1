<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $fillable = ['user_id', 'customer_code', 'date_of_birth', 'privacy_policy_version', 'consented_at'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'consented_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
