<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CancelEnrollmentAction
{
    public function execute(Enrollment $enrollment, bool $enforceCutoff = true): Enrollment
    {
        $classSession = $enrollment->classSession;
        $sessionStart = Carbon::parse($classSession->session_date.' '.$classSession->start_time);
        $cutoffHours = (int) config('enrollment.cancel_cutoff_hours');

        if ($enforceCutoff && now()->addHours($cutoffHours)->greaterThan($sessionStart)) {
            throw ValidationException::withMessages([
                'action' => __('flash.enrollmentCutoffPassed'),
            ]);
        }

        $wasBooked = $enrollment->status === 'booked';

        $enrollment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        if ($wasBooked) {
            Enrollment::where('class_session_id', $enrollment->class_session_id)
                ->where('status', 'waitlisted')
                ->orderBy('enrolled_at')
                ->first()
                ?->update(['status' => 'booked']);
        }

        return $enrollment;
    }
}
