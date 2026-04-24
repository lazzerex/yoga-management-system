<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(): Response
    {
        $logs = AuditLog::with('causer:id,name,username')
            ->latest('created_at')
            ->paginate(50);

        return inertia('Admin/AuditLogs/Index', ['logs' => $logs]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'audit-logs-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Performed By', 'Action', 'Target User', 'Details', 'Time']);

            AuditLog::with('causer:id,name,username')
                ->latest('created_at')
                ->chunk(200, function ($logs) use ($handle) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->causer?->name ?? 'System',
                            $log->action,
                            $log->subject_name ?? '—',
                            $log->meta ? json_encode($log->meta) : '—',
                            $log->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
