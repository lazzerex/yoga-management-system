<?php

namespace App\Notifications;

use App\Models\LessonPlan;
use Illuminate\Notifications\Messages\MailMessage;

class LessonPlanSubmittedNotification extends EventNotification
{
    public function __construct(public LessonPlan $lessonPlan) {}

    public function eventKey(): string
    {
        return 'lesson_plan.submitted';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params();

        return (new MailMessage)
            ->subject(__('notifications.lessonPlanSubmitted.subject', $params))
            ->line(__('notifications.lessonPlanSubmitted.line', $params))
            ->action(__('notifications.lessonPlanSubmitted.action'), $this->url());
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload('notifications.lessonPlanSubmitted.bell', $this->params(), $this->url());
    }

    private function params(): array
    {
        return [
            'title' => $this->lessonPlan->title,
            'coach' => $this->lessonPlan->coachProfile->user->name,
        ];
    }

    private function url(): string
    {
        return route('operations.lesson-plans.show', $this->lessonPlan);
    }
}
