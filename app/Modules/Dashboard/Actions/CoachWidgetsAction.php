<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\ClassSession;
use App\Models\LessonPlan;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Modules\Operations\Attendance\Actions\SessionBoardStatsAction;
use App\Modules\Operations\ClassSession\Actions\CoachWeeklyScheduleAction;

/**
 * A coach sees their own numbers, so these widgets are gated on coach.dashboard.view
 * plus an own-record scope. The operations.* permissions gate data about other people.
 */
class CoachWidgetsAction
{
    public function __construct(
        private SessionBoardStatsAction $sessionStats,
        private CoachWeeklyScheduleAction $weeklySchedule,
    ) {}

    public function execute(User $user): array
    {
        $coachProfile = $user->coachProfile;

        if (! $coachProfile) {
            return [];
        }

        return [
            'todaySessions' => $this->sessionStats->execute(today()->toDateString(), null, $coachProfile->id) + [
                'students' => $this->studentsToday($coachProfile->id),
                'url' => route('operations.teacher-attendance'),
            ],
            'teachingHours' => $this->teachingHours($coachProfile->id),
            'attendanceRate' => $this->attendanceRate($coachProfile->id),
            'myPlans' => $this->myPlans($coachProfile->id),
            'weekTimeline' => $this->weeklySchedule->execute($coachProfile),
        ];
    }

    private function studentsToday(int $coachProfileId): int
    {
        return ClassSession::where('coach_profile_id', $coachProfileId)
            ->where('session_date', today()->toDateString())
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->get(['id'])
            ->sum('booked_count');
    }

    private function teachingHours(int $coachProfileId): array
    {
        $minutes = TeacherAttendance::where('coach_profile_id', $coachProfileId)
            ->whereNotNull('checked_out_at')
            ->whereHas('classSession', fn ($q) => $q->whereBetween('session_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]))
            ->get()
            ->sum(fn (TeacherAttendance $attendance) => $attendance->taughtMinutes());

        return [
            'minutes' => (int) $minutes,
            'url' => route('operations.attendance.reports'),
        ];
    }

    private function attendanceRate(int $coachProfileId): array
    {
        $marks = StudentAttendance::whereHas('enrollment.classSession', fn ($q) => $q
            ->where('coach_profile_id', $coachProfileId)
            ->whereBetween('session_date', [
                today()->subDays(13)->toDateString(),
                today()->toDateString(),
            ]))
            ->get(['status']);

        $total = $marks->count();
        $attended = $marks->whereIn('status', ['present', 'late'])->count();

        return [
            'total' => $total,
            'attended' => $attended,
            'rate' => $total > 0 ? (int) round($attended / $total * 100) : 0,
        ];
    }

    private function myPlans(int $coachProfileId): array
    {
        $counts = LessonPlan::where('coach_profile_id', $coachProfileId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'counts' => collect(LessonPlan::STATUSES)
                ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
                ->all(),
            'url' => route('operations.lesson-planning'),
        ];
    }
}
