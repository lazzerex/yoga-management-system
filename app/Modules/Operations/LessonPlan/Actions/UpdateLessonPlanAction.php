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

        $attachments = $validated['attachments'] ?? [];
        unset($validated['attachments']);

        $lessonPlan->update($validated);

        foreach ($attachments as $attachment) {
            $lessonPlan->addMedia($attachment)->toMediaCollection('attachments');
        }

        return $lessonPlan;
    }
}
