<?php

namespace App\Modules\Operations\Attendance\Actions;

use App\Models\ClassSession;
use Illuminate\Support\Collection;

/** Shared by the attendance board and the dashboard so both quote the same figures. */
class SessionBoardStatsAction
{
    public function execute(string $date, ?int $branchId, ?int $coachProfileId): array
    {
        return $this->fromSessions(
            ClassSession::with('teacherAttendances')
                ->withCount($this->rosterCounts())
                ->where('session_date', $date)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($coachProfileId, fn ($q) => $q->where('coach_profile_id', $coachProfileId))
                ->get()
        );
    }

    /** @param  Collection<int, ClassSession>  $sessions  loaded with teacherAttendances and the roster counts */
    public function fromSessions(Collection $sessions): array
    {
        return [
            'sessions' => $sessions->count(),
            'checkedIn' => $sessions->filter(fn (ClassSession $session) => $session->teacherAttendances
                ->firstWhere('coach_profile_id', $session->coach_profile_id)?->checked_in_at !== null)->count(),
            'rostersComplete' => $sessions->filter(fn (ClassSession $session) => $session->booked_count > 0
                && $session->marked_count === $session->booked_count)->count(),
        ];
    }

    /** @return array<string, callable> the counts `fromSessions()` reads */
    public function rosterCounts(): array
    {
        return [
            'enrollments as booked_count' => fn ($q) => $q->where('status', 'booked'),
            'enrollments as marked_count' => fn ($q) => $q->where('status', 'booked')->whereHas('attendance'),
        ];
    }
}
