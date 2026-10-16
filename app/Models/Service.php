<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'description', 'image_url', 'target_audience', 'session_process',
        'duration_minutes', 'buffer_minutes',
        'price', 'status', 'display_order',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:0'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function specialists(): BelongsToMany
    {
        return $this->belongsToMany(SpecialistProfile::class, 'specialist_services');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
