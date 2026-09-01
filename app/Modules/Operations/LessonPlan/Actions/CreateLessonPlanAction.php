<?php

namespace App\Modules\Operations\LessonPlan\Actions;

use App\Models\CoachProfile;
use App\Models\LessonPlan;

class CreateLessonPlanAction
{
    public function execute(array $validated, CoachProfile $coachProfile): LessonPlan
    {
        return LessonPlan::create($validated + [
            'coach_profile_id' => $coachProfile->id,
            'status' => 'draft',
        ]);
    }
}
