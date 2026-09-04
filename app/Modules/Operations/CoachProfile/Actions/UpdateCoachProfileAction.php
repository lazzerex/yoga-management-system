<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;

class UpdateCoachProfileAction
{
    public function execute(CoachProfile $coachProfile, array $validated): CoachProfile
    {
        $classTypeIds = $validated['class_type_ids'] ?? [];
        $avatar = $validated['avatar'] ?? null;
        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);
        unset($validated['class_type_ids'], $validated['avatar'], $validated['remove_avatar']);

        $coachProfile->update($validated);
        $coachProfile->classTypes()->sync($classTypeIds);

        // The avatar hangs off the user, so it survives the profile being replaced.
        if ($removeAvatar) {
            $coachProfile->user->clearMediaCollection('avatar');
        }

        // The collection is singleFile, so a new upload replaces the one held.
        if ($avatar) {
            $coachProfile->user->addMedia($avatar)->toMediaCollection('avatar');
        }

        return $coachProfile;
    }
}
