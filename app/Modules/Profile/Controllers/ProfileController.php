<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $user->loadMax('loginLogs as last_login_at', 'logged_in_at')
            ->loadCount('loginLogs');

        $recentLogins = $user->loginLogs()
            ->latest('logged_in_at')
            ->limit(5)
            ->get(['ip_address', 'device_type', 'logged_in_at'])
            ->map(fn ($login) => [
                'ip_address' => $login->ip_address,
                'device_type' => $login->device_type,
                'logged_in_at' => $login->logged_in_at?->format('Y-m-d H:i'),
            ])
            ->values();

        return inertia('Profile/Show', [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'joined_at' => $user->created_at?->format('Y-m-d H:i'),
            ],
            'security' => [
                'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->format('Y-m-d H:i'),
            ],
            'loginStats' => [
                'total_sign_ins' => $user->login_logs_count,
                'last_login_at' => $user->last_login_at
                    ? Carbon::parse($user->last_login_at)->format('Y-m-d H:i')
                    : null,
            ],
            'recentLogins' => $recentLogins,
        ]);
    }
}
