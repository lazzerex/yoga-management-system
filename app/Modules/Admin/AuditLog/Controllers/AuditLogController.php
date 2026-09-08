<?php

namespace App\Modules\Admin\AuditLog\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Table\SortsQueries;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    use SortsQueries;

    private const VALID_ACTIONS = ['create_user', 'update_user_info', 'change_password', 'assign_role', 'remove_role', 'delete_user', 'view_student_medical_notes', 'cancel_enrollment', 'update_setting', 'ai_attachment_sent'];

    public function index(Request $request): Response
    {
        $query = AuditLog::with('causer:id,name,username');

        if ($request->filled('action') && in_array($request->action, self::VALID_ACTIONS)) {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                    ->orWhereHas('causer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $query->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $sort = $this->applySort($query, $request, [
            'action' => 'action',
            'created_at' => 'created_at',
        ], 'created_at');

        $logs = $query->paginate(15)->withQueryString();

        return inertia('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->string('action')->toString(),
                'search' => $request->string('search')->toString(),
                'from' => $from ?? '',
                'to' => $to ?? '',
            ] + $sort,
            'endpoints' => [
                'self' => route('admin.audit-logs.index'),
                'export' => route('admin.audit-logs.export'),
                'login_logs' => route('admin.login-logs.index'),
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'audit-logs-'.now()->format('Y-m-d').'.csv';

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
