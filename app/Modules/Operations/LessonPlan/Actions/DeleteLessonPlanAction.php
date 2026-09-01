<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\LessonPlan;
use Illuminate\Validation\ValidationException;

class DeleteLessonPlanAction
{
    public function execute(LessonPlan $lessonPlan): void
    {
        if ($lessonPlan->status !== 'draft') {
            throw ValidationException::withMessages([
                'action' => __('flash.lessonPlanNotDeletable'),
            ]);
        }

        $lessonPlan->delete();
    }
}
