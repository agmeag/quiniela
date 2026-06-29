<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function log(
        string $action,
        string $description,
        ?string $resourceType = null,
        ?int $resourceId = null,
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name ?? 'Sistema',
            'user_role'     => $user?->role ?? null,
            'action'        => $action,
            'description'   => $description,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'ip_address'    => request()->ip(),
            'created_at'    => now(),
        ]);
    }
}
