<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Profile\Requests\UpdateAvatarRequest;
use App\Modules\Profile\Requests\UpdateOwnCoachProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
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
                'avatar_url' => $user->avatarThumbUrl(),
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'joined_at' => $user->created_at?->format('Y-m-d H:i'),
            ],
            'coachProfile' => $user->coachProfile?->only(['bio', 'years_experience', 'certifications']),
            'loginStats' => [
                'total_sign_ins' => $user->login_logs_count,
                'last_login_at' => $user->last_login_at
                    ? Carbon::parse($user->last_login_at)->format('Y-m-d H:i')
                    : null,
            ],
            'recentLogins' => $recentLogins,
            'notificationEvents' => $this->notificationCatalogue($user),
            'notificationChannels' => config('notifications.channels'),
            'endpoints' => [
                'avatar' => route('cms.profile.avatar'),
                'account' => route('user-profile-information.update'),
                'notificationPreferences' => route('cms.profile.notification-preferences'),
                'coachProfile' => $user->coachProfile && $user->can('coach.dashboard.view') ? route('coach.profile.update') : null,
            ],
        ]);
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        $catalogue = config('notifications.events');
        $deviations = [];

        foreach ($request->input('preferences', []) as $eventKey => $channels) {
            $event = $catalogue[$eventKey] ?? null;

            if (! $event || ! $this->canReceive($user, $event)) {
                continue;
            }

            foreach ($this->activeChannels($event) as $channel => $default) {
                $wanted = (bool) ($channels[$channel] ?? $default);

                if ($wanted !== $default) {
                    $deviations[$eventKey][$channel] = $wanted;
                }
            }
        }

        $user->update(['notification_preferences' => $deviations]);

        return back()->with('success', ['key' => 'flash.notificationPreferencesUpdated']);
    }

    private function notificationCatalogue(User $user): array
    {
        return collect(config('notifications.events'))
            ->filter(fn (array $event) => $this->canReceive($user, $event))
            ->map(fn (array $event, string $key) => [
                'key' => $key,
                'label' => __($event['label']),
                'channels' => collect($this->activeChannels($event))
                    ->map(fn ($default, string $channel) => $user->wantsNotification($key, $channel))
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function canReceive(User $user, array $event): bool
    {
        return collect($event['audience'])->contains(fn (string $permission) => $user->can($permission));
    }

    private function activeChannels(array $event): array
    {
        return array_intersect_key($event['channels'], array_flip(config('notifications.channels')));
    }

    public function updateCoachProfile(UpdateOwnCoachProfileRequest $request): RedirectResponse
    {
        $coachProfile = $request->user()->coachProfile;

        abort_unless($coachProfile, 404);

        $coachProfile->update($request->validated());

        return back()->with('success', ['key' => 'flash.coachProfileUpdated', 'params' => ['name' => $request->user()->name]]);
    }

    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($validated['remove_avatar'] ?? false) {
            $user->clearMediaCollection('avatar');
        }

        // The collection is singleFile, so a new upload replaces the one held.
        if ($validated['avatar'] ?? null) {
            $user->addMedia($validated['avatar'])->toMediaCollection('avatar');
        }

        return back()->with('success', ['key' => 'flash.avatarUpdated']);
    }
}
