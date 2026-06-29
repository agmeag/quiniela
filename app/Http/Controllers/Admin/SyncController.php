<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FullSyncJob;
use App\Models\SyncLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SyncController extends Controller
{
    public function trigger(): JsonResponse
    {
        if (Cache::get('admin:sync:status')) {
            return response()->json(['error' => 'Sincronización ya en progreso.'], 409);
        }

        Cache::put('admin:sync:status', 'queued', 600);
        FullSyncJob::dispatch();

        AuditService::log('sync.triggered', "Sincronización y recálculo completo disparado manualmente");

        return response()->json(['status' => 'queued']);
    }

    public function status(): JsonResponse
    {
        $status  = Cache::get('admin:sync:status', 'idle');
        $lastSync = SyncLog::where('type', 'full_sync')->latest()->first();

        return response()->json([
            'status'    => $status,
            'last_sync' => $lastSync ? [
                'status'           => $lastSync->status,
                'matches_fetched'  => $lastSync->matches_fetched,
                'matches_updated'  => $lastSync->matches_updated,
                'duration_seconds' => $lastSync->duration_seconds,
                'created_at'       => $lastSync->created_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
