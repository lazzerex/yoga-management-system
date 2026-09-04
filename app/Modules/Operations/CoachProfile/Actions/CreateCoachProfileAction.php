<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;

class CreateCoachProfileAction
{
    public function execute(array $validated): CoachProfile
    {
        $classTypeIds = $validated['class_type_ids'] ?? [];
        $avatar = $validated['avatar'] ?? null;
        unset($validated['class_type_ids'], $validated['avatar'], $validated['remove_avatar']);

        $profile = CoachProfile::create($validated);
        $profile->classTypes()->sync($classTypeIds);

        if ($avatar) {
            $profile->user->addMedia($avatar)->toMediaCollection('avatar');
        }

        return $profile;
    }
}
