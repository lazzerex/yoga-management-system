<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;

class DeleteStudentProfileAction
{
    public function execute(StudentProfile $studentProfile): void
    {
        $studentProfile->delete();
    }
}
