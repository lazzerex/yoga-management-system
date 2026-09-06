<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;

class EnrollmentPromotedNotification extends EventNotification
{
    public function __construct(public Enrollment $enrollment) {}

    public function eventKey(): string
    {
        return 'enrollment.promoted';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.enrollmentPromoted.subject', $params))
            ->line(__('notifications.enrollmentPromoted.line', $params))
            ->action(__('notifications.enrollmentPromoted.action'), route('member.my-classes'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.enrollmentPromoted.bell', $this->params(), route('member.my-classes'));
    }

    private function params(): array
    {
        $session = $this->enrollment->classSession;

        return [
            'class' => $session->classType->name,
            'date' => $session->session_date,
            'time' => $session->start_time,
        ];
    }
}
