<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $category = $request->query('category', '');
        $user     = $request->query('user', '');

        $query = AuditLog::query()->orderByDesc('created_at');

        if ($category) {
            $query->where('action', 'like', $category . '.%');
        }

        if ($user) {
            $query->where('user_name', 'like', '%' . $user . '%');
        }

        $logs = $query->paginate(60)->withQueryString()->through(fn ($log) => [
            'id'            => $log->id,
            'user_name'     => $log->user_name,
            'user_role'     => $log->user_role,
            'action'        => $log->action,
            'description'   => $log->description,
            'resource_type' => $log->resource_type,
            'resource_id'   => $log->resource_id,
            'ip_address'    => $log->ip_address,
            'created_at'    => $log->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/AuditLogs', [
            'logs'    => $logs,
            'filters' => ['category' => $category, 'user' => $user],
        ]);
    }
}
