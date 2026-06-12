<?php

namespace App\Modules\Admin\LoginLog\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoginLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LoginLog::with('user:id,name,username');

        if ($request->filled('status') && in_array($request->status, ['success', 'failed'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('device') && in_array($request->device, ['mobile', 'desktop'])) {
            $query->where('device_type', $request->device);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('attempted_identifier', 'like', "%{$search}%");
            });
        }

        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('logged_in_at', $sortDir);

        $logs = $query->paginate(15)->withQueryString();

        return inertia('Admin/LoginLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['status', 'device', 'search', 'sort_dir']),
            'endpoints' => [
                'self' => route('admin.login-logs.index'),
                'export' => route('admin.login-logs.export'),
                'users' => route('admin.users.index'),
                'audit_logs' => route('admin.audit-logs.index'),
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'login-logs-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Status', 'User', 'Identifier', 'IP Address', 'Device', 'Failure Reason', 'Time']);

            LoginLog::with('user:id,name,username')
                ->latest('logged_in_at')
                ->chunk(200, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->status,
                            $log->user?->name ?? '—',
                            $log->attempted_identifier ?? '—',
                            $log->ip_address,
                            $log->device_type,
                            $log->failure_reason ?? '—',
                            $log->logged_in_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
