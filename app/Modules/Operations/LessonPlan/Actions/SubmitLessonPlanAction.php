<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\LessonPlan;
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

        return $lessonPlan;
    }
}
