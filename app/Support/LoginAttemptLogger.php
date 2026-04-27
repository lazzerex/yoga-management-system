<?php

namespace App\Support;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginAttemptLogger
{
    public static function recordFailed(Request $request, string $failureReason, ?User $user = null): void
    {
        $identifier = trim((string) $request->input('username', $request->input('email', '')));
        $userAgent = (string) ($request->userAgent() ?? '');

        LoginLog::create([
            'user_id' => $user?->id,
            'status' => LoginLog::STATUS_FAILED,
            'attempted_identifier' => $identifier !== '' ? $identifier : $user?->username,
            'failure_reason' => $failureReason,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent !== '' ? $userAgent : null,
            'device_type' => self::detectDevice($userAgent),
            'logged_in_at' => now(),
        ]);
    }

    private static function detectDevice(string $userAgent): string
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)
            ? 'mobile'
            : 'desktop';
    }
}
