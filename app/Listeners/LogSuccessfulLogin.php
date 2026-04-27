<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $request = request();
        $userAgent = $request->userAgent() ?? '';

        LoginLog::create([
            'user_id' => $event->user->id,
            'status' => LoginLog::STATUS_SUCCESS,
            'attempted_identifier' => $event->user->username,
            'failure_reason' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $this->detectDevice($userAgent),
            'logged_in_at' => now(),
        ]);
    }

    private function detectDevice(string $userAgent): string
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)
            ? 'mobile'
            : 'desktop';
    }
}
