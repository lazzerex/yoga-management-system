<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;

class UpdateCoachProfileAction
{
    public function execute(CoachProfile $coachProfile, array $validated): CoachProfile
    {
        $classTypeIds = $validated['class_type_ids'] ?? [];
        unset($validated['class_type_ids']);

        $coachProfile->update($validated);
        $coachProfile->classTypes()->sync($classTypeIds);

        return $coachProfile;
    }
}
