<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Validation\ValidationException;

class CreateEnrollmentAction
{
    public function execute(StudentProfile $studentProfile, ClassSession $classSession): Enrollment
    {
        if ($classSession->status !== 'scheduled' || $classSession->session_date < now()->toDateString()) {
            throw ValidationException::withMessages([
                'enrollment' => __('flash.enrollmentSessionUnavailable'),
            ]);
        }

        $alreadyEnrolled = $classSession->enrollments()
            ->where('student_profile_id', $studentProfile->id)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($alreadyEnrolled) {
            throw ValidationException::withMessages([
                'enrollment' => __('flash.enrollmentAlreadyExists'),
            ]);
        }

        $bookedCount = $classSession->enrollments()->where('status', 'booked')->count();

        return Enrollment::create([
            'student_profile_id' => $studentProfile->id,
            'class_session_id' => $classSession->id,
            'status' => $bookedCount < $classSession->capacity ? 'booked' : 'waitlisted',
            'enrolled_at' => now(),
        ]);
    }
}
