<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoginLogController extends Controller
{
    public function index(): Response
    {
        $logs = LoginLog::with('user:id,name,username')
            ->latest('logged_in_at')
            ->paginate(50);

        return inertia('Admin/LoginLogs/Index', ['logs' => $logs]);
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
