<?php

namespace App\Modules\Operations\ClassSession\Actions;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use Carbon\CarbonInterface;

/** Shared by the teaching schedule page and the coach dashboard. Without a week, the next seven days. */
class CoachWeeklyScheduleAction
{
    public function execute(?CoachProfile $coachProfile, ?CarbonInterface $weekStart = null): array
    {
        if (! $coachProfile) {
            return [];
        }

        $from = $weekStart ?? now();

        return ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name'])
            ->withCount([
                'enrollments as booked_count' => fn ($q) => $q->where('status', 'booked'),
                'enrollments as waitlist_count' => fn ($q) => $q->where('status', 'waitlisted'),
            ])
            ->where('coach_profile_id', $coachProfile->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('session_date', [$from->toDateString(), $from->copy()->addDays(6)->toDateString()])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'reference' => $session->reference(),
                'session_date' => $session->session_date,
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'status' => $session->status,
                'class_type_id' => $session->class_type_id,
                'class_type_name' => $session->classType->name,
                'branch_name' => $session->branch->name,
                'room_name' => $session->room->name,
                'capacity' => $session->capacity,
                'students' => $session->booked_count,
                'waitlist_count' => $session->waitlist_count,
            ])
            ->all();
    }
}
