<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;

class CreateStudentProfileAction
{
    public function execute(array $validated): StudentProfile
    {
        return StudentProfile::create($validated);
    }
}
