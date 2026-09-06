<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;

class EnrollmentCancelledByStaffNotification extends EventNotification
{
    public function __construct(public Enrollment $enrollment) {}

    public function eventKey(): string
    {
        return 'enrollment.cancelled_by_staff';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.enrollmentCancelledByStaff.subject', $params))
            ->line(__('notifications.enrollmentCancelledByStaff.line', $params))
            ->action(__('notifications.enrollmentCancelledByStaff.action'), route('member.my-classes'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.enrollmentCancelledByStaff.bell', $this->params(), route('member.my-classes'));
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
