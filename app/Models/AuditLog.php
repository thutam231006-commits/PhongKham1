<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'actor_role', 'action', 'entity_type', 'entity_id',
        'old_value', 'new_value', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return ['old_value' => 'array', 'new_value' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
