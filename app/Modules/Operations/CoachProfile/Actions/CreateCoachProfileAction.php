<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;

class CreateCoachProfileAction
{
    public function execute(array $validated): CoachProfile
    {
        $classTypeIds = $validated['class_type_ids'] ?? [];
        unset($validated['class_type_ids']);

        $profile = CoachProfile::create($validated);
        $profile->classTypes()->sync($classTypeIds);

        return $profile;
    }
}
