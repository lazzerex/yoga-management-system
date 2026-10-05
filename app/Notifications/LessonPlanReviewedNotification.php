<?php

namespace App\Notifications;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class LessonPlanReviewedNotification extends EventNotification
{
    public function __construct(public LessonPlan $lessonPlan, public User $reviewer) {}

    public function eventKey(): string
    {
        return 'lesson_plan.reviewed';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $params = $this->params() + ['status' => __('notifications.status.'.$this->lessonPlan->status)];

        return (new MailMessage)
            ->subject(__('notifications.lessonPlanReviewed.subject', $params))
            ->line(__('notifications.lessonPlanReviewed.line', $params))
            ->action(__('notifications.lessonPlanReviewed.action'), $this->url());
    }

    public function toArray(object $notifiable): array
    {
        // The bell renders in the reader's language later, so the outcome picks the key instead of a translated word.
        $key = $this->lessonPlan->status === 'approved' ? 'bellApproved' : 'bellRejected';

        return $this->payload('notifications.lessonPlanReviewed.'.$key, $this->params(), $this->url());
    }

    private function params(): array
    {
        return [
            'title' => $this->lessonPlan->title,
            'reviewer' => $this->reviewer->name,
        ];
    }

    private function url(): string
    {
        return route('operations.lesson-plans.show', $this->lessonPlan);
    }
}
