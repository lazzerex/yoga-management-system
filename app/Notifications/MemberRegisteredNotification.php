<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class MemberRegisteredNotification extends EventNotification
{
    public function __construct(public User $registered) {}

    public function eventKey(): string
    {
        return 'member.registered';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.memberRegistered.subject', $params))
            ->line(__('notifications.memberRegistered.line', $params))
            ->action(__('notifications.memberRegistered.action'), $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.memberRegistered.bell', $this->params(), $this->url());
    }

    private function params(): array
    {
        return [
            'name' => $this->registered->name,
            'username' => $this->registered->username,
        ];
    }

    private function url(): string
    {
        return route('admin.users.edit', $this->registered);
    }
}
