<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\LessonPlan;
use App\Models\User;
use App\Notifications\LessonPlanSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SubmitLessonPlanAction
{
    public function execute(LessonPlan $lessonPlan): LessonPlan
    {
        if (! $lessonPlan->isEditable()) {
            throw ValidationException::withMessages([
                'action' => __('flash.lessonPlanNotSubmittable'),
            ]);
        }

        $lessonPlan->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        Notification::send(
            User::permission('operations.plans.review')->get(),
            new LessonPlanSubmittedNotification($lessonPlan)
        );

        return $lessonPlan;
    }
}
