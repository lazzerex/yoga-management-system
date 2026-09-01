<?php

namespace App\Modules\Operations\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\TeacherAttendance;
use Illuminate\Validation\ValidationException;

class CheckInCoachAction
{
    public function execute(ClassSession $classSession): TeacherAttendance
    {
        if ($classSession->status === 'cancelled') {
            throw ValidationException::withMessages([
                'action' => __('flash.attendanceSessionCancelled'),
            ]);
        }

        $existing = TeacherAttendance::where('class_session_id', $classSession->id)
            ->where('coach_profile_id', $classSession->coach_profile_id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'action' => __('flash.attendanceAlreadyCheckedIn'),
            ]);
        }

        return TeacherAttendance::create([
            'class_session_id' => $classSession->id,
            'coach_profile_id' => $classSession->coach_profile_id,
            'checked_in_at' => now(),
        ]);
    }
}
