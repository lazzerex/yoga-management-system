<?php

namespace App\Modules\Operations\ClassSession\Actions;

use App\Models\ClassSession;
use App\Models\CoachProfile;

/** Shared by the teaching schedule page and the coach dashboard. */
class CoachWeeklyScheduleAction
{
    public function execute(?CoachProfile $coachProfile): array
    {
        if (! $coachProfile) {
            return [];
        }

        return ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name'])
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->where('coach_profile_id', $coachProfile->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('session_date', [now()->toDateString(), now()->addDays(6)->toDateString()])
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'session_date' => $session->session_date,
                'start_time' => substr($session->start_time, 0, 5),
                'class_type_name' => $session->classType->name,
                'branch_name' => $session->branch->name,
                'room_name' => $session->room->name,
                'students' => $session->booked_count,
            ])
            ->all();
    }
}
