<?php

namespace App\Modules\Operations\Enrollment\Actions;

use App\Models\Enrollment;
use App\Notifications\EnrollmentPromotedNotification;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class CancelEnrollmentAction
{
    public function execute(Enrollment $enrollment, bool $enforceCutoff = true): Enrollment
    {
        $classSession = $enrollment->classSession;
        $sessionStart = Carbon::parse($classSession->session_date.' '.$classSession->start_time);
        $cutoffHours = (int) Settings::get('booking.cancel_cutoff_hours', config('enrollment.cancel_cutoff_hours'));

        if ($enforceCutoff && now()->addHours($cutoffHours)->greaterThan($sessionStart)) {
            throw ValidationException::withMessages([
                'action' => __('flash.enrollmentCutoffPassed'),
            ]);
        }

        $wasBooked = $enrollment->status === 'booked';

        $promoted = DB::transaction(function () use ($enrollment, $wasBooked) {
            $enrollment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            if (! $wasBooked) {
                return null;
            }

            $next = Enrollment::where('class_session_id', $enrollment->class_session_id)
                ->where('status', 'waitlisted')
                ->orderBy('enrolled_at')
                ->first();

            $next?->update(['status' => 'booked']);

            return $next;
        });

        // After the transaction, never inside it: a rolled back job is the double-send bug.
        if ($promoted && $user = $promoted->studentProfile->user) {
            Notification::send($user, new EnrollmentPromotedNotification($promoted));
        }

        return $enrollment;
    }
}
