<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Inertia\Response;

class LoginLogController extends Controller
{
    public function index(): Response
    {
        $logs = LoginLog::with('user:id,name,username')
            ->latest('logged_in_at')
            ->paginate(50);

        return inertia('Admin/LoginLogs/Index', ['logs' => $logs]);
    }
}
