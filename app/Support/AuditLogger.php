<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(string $eventType, ?Model $auditable = null, ?array $payload = null, ?int $userId = null): void
    {
        AuditLog::query()->create([
            'user_id' => $userId ?? (auth()->id() ?: null),
            'event_type' => $eventType,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'payload' => $payload,
        ]);
    }
}
