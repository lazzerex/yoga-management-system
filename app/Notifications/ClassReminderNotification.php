<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Notifications\Messages\MailMessage;

class ClassReminderNotification extends EventNotification
{
    public function __construct(public ClassSession $classSession) {}

    public function eventKey(): string
    {
        return 'class.reminder';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.classReminder.subject', $params))
            ->line(__('notifications.classReminder.line', $params))
            ->action(__('notifications.classReminder.action'), $this->url($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.classReminder.bell', $this->params(), $this->url($notifiable));
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
