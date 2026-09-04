<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;

class UpdateStudentProfileAction
{
    public function execute(StudentProfile $studentProfile, array $validated): StudentProfile
    {
        $avatar = $validated['avatar'] ?? null;
        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);
        unset($validated['avatar'], $validated['remove_avatar']);

        $studentProfile->update($validated);

        if ($removeAvatar) {
            $studentProfile->user->clearMediaCollection('avatar');
        }

        // The collection is singleFile, so a new upload replaces the one held.
        if ($avatar) {
            $studentProfile->user->addMedia($avatar)->toMediaCollection('avatar');
        }

        return $studentProfile;
    }
}
