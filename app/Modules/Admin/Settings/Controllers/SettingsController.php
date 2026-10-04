<?php

namespace App\Modules\Admin\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Settings\Requests\UpdateSettingsRequest;
use App\Modules\Admin\User\Actions\AuditUserAction;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SettingsController extends Controller
{
    // Field name to setting key. Anything outside this map is never read and never stored.
    private const KEYS = [
        'centre_name' => 'centre.name',
        'cancel_cutoff_hours' => 'booking.cancel_cutoff_hours',
        'require_entitlement' => 'booking.require_entitlement',
        'default_locale' => 'centre.default_locale',
    ];

    public function __construct(private AuditUserAction $audit) {}

    public function general(Request $request): Response
    {
        return inertia('Admin/Settings/Settings', [
            'title' => 'General',
            'live' => $this->live(),
            'canManage' => $request->user()->can('admin.settings.manage'),
        ]);
    }

    public function systemGeneral(): Response
    {
        return inertia('Admin/Settings/Settings', [
            'title' => 'System / General',
            'live' => [],
            'canManage' => false,
        ]);
    }

    public function systemAdvanced(): Response
    {
        return inertia('Admin/Settings/Settings', [
            'title' => 'System / Advanced',
            'live' => [],
            'canManage' => false,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $changes = [];

        foreach (self::KEYS as $field => $key) {
            $before = (string) Settings::get($key, '');
            $after = (string) $validated[$field];

            if ($before === $after) {
                continue;
            }

            Settings::set($key, $after);

            $changes[] = ['key' => $key, 'from' => $before, 'to' => $after];
        }

        // One row per save, not per field.
        if ($changes !== []) {
            $this->audit->execute($user, 'update_setting', $user, ['changes' => $changes]);
        }

        return back()->with('success', __('flash.settingsUpdated'));
    }

    /**
     * @return array<string, string>
     */
    private function live(): array
    {
        return [
            'centre_name' => (string) Settings::get('centre.name', config('app.name')),
            'cancel_cutoff_hours' => (string) Settings::get('booking.cancel_cutoff_hours', config('enrollment.cancel_cutoff_hours')),
            // '1'/'0', not a boolean: the save loop stringifies, and false would become ''.
            'require_entitlement' => (string) (int) Settings::get('booking.require_entitlement', config('enrollment.require_entitlement')),
            'default_locale' => (string) Settings::get('centre.default_locale', 'en'),
        ];
    }
}
