<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CancelEnrollmentAction
{
    // No cutoff length was specified by the business; 2h before session start is a placeholder
    // pending a real number from the client.
    private const CUTOFF_HOURS = 2;

    public function execute(Enrollment $enrollment): Enrollment
    {
        $classSession = $enrollment->classSession;
        $sessionStart = Carbon::parse($classSession->session_date.' '.$classSession->start_time);

        if (now()->addHours(self::CUTOFF_HOURS)->greaterThan($sessionStart)) {
            throw ValidationException::withMessages([
                'enrollment' => __('flash.enrollmentCutoffPassed'),
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
