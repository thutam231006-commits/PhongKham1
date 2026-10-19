<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /** @param array<string, mixed>|null $old @param array<string, mixed>|null $new */
    public function record(string $action, Model|string $entity, int|string|null $entityId = null, ?array $old = null, ?array $new = null, ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();
        $entityType = $entity instanceof Model ? $entity->getTable() : $entity;
        $entityId ??= $entity instanceof Model ? $entity->getKey() : null;

        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'actor_role' => strtoupper((string) ($actor?->role ?? 'SYSTEM')),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => is_numeric($entityId) ? (int) $entityId : null,
            'old_value' => $old,
            'new_value' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);
    }
}
