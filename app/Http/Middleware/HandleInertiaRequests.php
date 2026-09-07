<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Modules\Profile\Controllers\NotificationController;
use App\Modules\Search\Controllers\SearchController;
use App\Support\Menu\Facades\Menu;
use App\Support\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentBranch = $request->attributes->get('currentBranch');

        return array_merge(parent::share($request), [
            // Lazy: share() runs before SetLocale, so a plain value would resolve its fallback under 'en'.
            'centreName' => fn () => Settings::get('centre.name') ?: __('dashboard.systemName'),
            'currentBranch' => $currentBranch ? ['id' => $currentBranch->id, 'name' => $currentBranch->name] : null,
            'allBranches' => fn () => Branch::active()->orderBy('name')->get(['id', 'name']),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'canAccessAdmin' => $user->canAccessAdmin(),
                    'canViewCoachDashboard' => $user->can('coach.dashboard.view'),
                    'canSearch' => SearchController::isAvailableTo($user),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Not 'notifications': page props merge over shared ones and would swallow it.
            'bell' => [
                'unread' => fn () => $user ? $user->unreadNotifications()->count() : 0,
                'recent' => Inertia::optional(fn () => $user ? NotificationController::recentFor($user) : []),
            ],
            'menu' => fn () => Menu::forUser($user),
        ]);
    }
}
