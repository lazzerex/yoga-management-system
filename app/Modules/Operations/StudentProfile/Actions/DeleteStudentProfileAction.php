<?php

namespace App\Modules\Operations\StudentProfile\Actions;

use App\Models\StudentProfile;
use Illuminate\Validation\ValidationException;

class DeleteStudentProfileAction
{
    public function execute(StudentProfile $studentProfile): void
    {
        if ($studentProfile->enrollments()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.studentProfileHasEnrollments'),
            ]);
        }

        $studentProfile->delete();
    }
}
