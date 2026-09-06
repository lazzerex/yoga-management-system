<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class EventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function eventKey(): string;

    abstract public function toArray(object $notifiable): array;

    public function via(object $notifiable): array
    {
        return array_values(array_filter(
            config('notifications.channels'),
            fn (string $channel) => $notifiable->wantsNotification($this->eventKey(), $channel)
        ));
    }

    // Inline for the bell; mail keeps the default queue connection when it is turned on.
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    protected function payload(string $message, array $params, string $url): array
    {
        return [
            'event' => $this->eventKey(),
            'message' => $message,
            'params' => $params,
            'url' => $url,
        ];
    }
}
