<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return inertia('Notifications/Index', [
            'notifications' => [
                'data' => collect($notifications->items())->map(fn ($row) => self::row($row))->all(),
                'links' => $notifications->linkCollection()->toArray(),
                'total' => $notifications->total(),
            ],
            'endpoints' => [
                'readAll' => route('cms.notifications.read-all'),
                'clear' => route('cms.notifications.clear'),
            ],
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('success', ['key' => 'flash.notificationsCleared']);
    }

    public static function recentFor(User $user): array
    {
        return $user->notifications()->limit(5)->get()->map(fn ($row) => self::row($row))->all();
    }

    private static function row(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'event' => $notification->data['event'] ?? null,
            'message' => $notification->data['message'] ?? '',
            'params' => $notification->data['params'] ?? [],
            'url' => $notification->data['url'] ?? null,
            'emailed' => (bool) ($notification->data['emailed'] ?? false),
            'read' => $notification->read_at !== null,
            'time' => $notification->created_at->diffForHumans(),
            'readUrl' => route('cms.notifications.read', $notification->id),
        ];
    }
}
