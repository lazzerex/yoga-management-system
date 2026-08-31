<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($studentProfile, $classSession) {
            $active = $classSession->enrollments()
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->get();

            if ($active->contains('student_profile_id', $studentProfile->id)) {
                throw ValidationException::withMessages([
                    'enrollment' => __('flash.enrollmentAlreadyExists'),
                ]);
            }

            $bookedCount = $active->where('status', 'booked')->count();

            return Enrollment::create([
                'student_profile_id' => $studentProfile->id,
                'class_session_id' => $classSession->id,
                'status' => $bookedCount < $classSession->capacity ? 'booked' : 'waitlisted',
                'enrolled_at' => now(),
            ]);
        });
    }
}
