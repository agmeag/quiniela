<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Prediction;
use App\Models\SyncLog;
use App\Services\AuditService;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $lastSync = SyncLog::where('status', 'success')->latest()->first();
        $lastImport = SyncLog::where('type', 'excel_import')->latest()->first();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'participants'             => Participant::count(),
                'predictions'              => Prediction::count(),
                'last_sync'                => $lastSync?->created_at,
                'last_import'              => $lastImport?->created_at,
                'import_result'            => $lastImport?->meta,
                'participants_without_users' => \App\Models\Participant::whereNotNull('email')
                    ->whereDoesntHave('user')
                    ->count(),
            ],
        ]);
    }

    public function import(Request $request, ExcelImportService $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $fullPath = storage_path("app/private/{$path}");

        try {
            $stats = $importer->import($fullPath);

            SyncLog::create([
                'type'     => 'excel_import',
                'status'   => 'success',
                'message'  => 'Import completed',
                'meta' => $stats,
            ]);

            Cache::tags(['leaderboard', 'matches'])->flush();

            AuditService::log('import.completed', "Importación Excel exitosa: {$stats['participants']} participantes, {$stats['predictions']} pronósticos");

            return back()->with('success', "Importación exitosa: {$stats['participants']} participantes, {$stats['predictions']} pronósticos.");
        } catch (Throwable $e) {
            SyncLog::create([
                'type'    => 'excel_import',
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ]);

            AuditService::log('import.failed', "Importación Excel fallida: {$e->getMessage()}");

            return back()->with('error', 'Error durante la importación: '.$e->getMessage());
        }
    }
}
