<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\LessonPlan;
use Illuminate\Validation\ValidationException;

class UpdateLessonPlanAction
{
    public function execute(LessonPlan $lessonPlan, array $validated): LessonPlan
    {
        if (! $lessonPlan->isEditable()) {
            throw ValidationException::withMessages([
                'action' => __('flash.lessonPlanLocked'),
            ]);
        }

        $lessonPlan->update($validated);

        return $lessonPlan;
    }
}
