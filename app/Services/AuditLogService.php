<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        Request $request,
        string $action,
        ?Model $auditable = null,
        array $metadata = [],
        ?array $oldValues = null,
        ?array $newValues = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'request_id' => (string) $request->attributes->get('request_id', ''),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
