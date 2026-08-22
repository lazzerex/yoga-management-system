<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;

class UpdateStudentProfileAction
{
    public function execute(StudentProfile $studentProfile, array $validated): StudentProfile
    {
        $studentProfile->update($validated);

        return $studentProfile;
    }
}
