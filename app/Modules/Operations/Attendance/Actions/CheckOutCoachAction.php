<?php

namespace App\Modules\Operations\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\TeacherAttendance;
use Illuminate\Validation\ValidationException;

class CheckOutCoachAction
{
    public function execute(ClassSession $classSession): TeacherAttendance
    {
        $attendance = TeacherAttendance::where('class_session_id', $classSession->id)
            ->where('coach_profile_id', $classSession->coach_profile_id)
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'action' => __('flash.attendanceNotCheckedIn'),
            ]);
        }

        if ($attendance->checked_out_at) {
            throw ValidationException::withMessages([
                'action' => __('flash.attendanceAlreadyCheckedOut'),
            ]);
        }

        $attendance->update(['checked_out_at' => now()]);

        return $attendance;
    }
}
