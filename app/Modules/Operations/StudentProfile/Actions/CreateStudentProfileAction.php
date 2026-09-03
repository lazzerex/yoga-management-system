<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;

class CreateStudentProfileAction
{
    public function execute(array $validated): StudentProfile
    {
        $avatar = $validated['avatar'] ?? null;
        unset($validated['avatar'], $validated['remove_avatar']);

        $profile = StudentProfile::create($validated);

        if ($avatar) {
            $profile->addMedia($avatar)->toMediaCollection('avatar');
        }

        return $profile;
    }
}
