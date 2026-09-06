<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Notifications\Messages\MailMessage;

class ClassCancelledNotification extends EventNotification
{
    public function __construct(public ClassSession $classSession) {}

    public function eventKey(): string
    {
        return 'class.cancelled';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.classCancelled.subject', $params))
            ->line(__('notifications.classCancelled.line', $params))
            ->action(__('notifications.classCancelled.action'), $this->url($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.classCancelled.bell', $this->params(), $this->url($notifiable));
    }

    private function params(): array
    {
        return [
            'class' => $this->classSession->classType->name,
            'date' => $this->classSession->session_date,
            'time' => $this->classSession->start_time,
        ];
    }

    private function url(object $notifiable): string
    {
        return $notifiable->can('coach.dashboard.view')
            ? route('coach.my-teaching-schedule')
            : route('member.my-schedule');
    }
}
