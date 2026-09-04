<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\TeacherAttendance;
use Illuminate\Support\Carbon;

/** A coach's own taught time over the last twelve weeks. */
class MyTeachingAction
{
    private const WEEKS = 12;

    public function execute(?int $coachProfileId): array
    {
        $from = today()->startOfWeek()->subWeeks(self::WEEKS - 1);

        if (! $coachProfileId) {
            return ['weeks' => self::WEEKS, 'weekly' => [], 'totalMinutes' => 0];
        }

        $attendances = TeacherAttendance::with('classSession:id,session_date')
            ->where('coach_profile_id', $coachProfileId)
            ->whereNotNull('checked_out_at')
            ->whereHas('classSession', fn ($q) => $q
                ->whereBetween('session_date', [$from->toDateString(), today()->toDateString()]))
            ->get();

        $byWeek = $attendances->groupBy(fn (TeacherAttendance $row) => Carbon::parse($row->classSession->session_date)
            ->startOfWeek()
            ->toDateString());

        return [
            'weeks' => self::WEEKS,
            'weekly' => collect(range(0, self::WEEKS - 1))
                ->map(function (int $offset) use ($byWeek, $from) {
                    $week = $from->copy()->addWeeks($offset);
                    $group = $byWeek[$week->toDateString()] ?? collect();

                    return [
                        'week' => $week->format('d/m'),
                        'minutes' => (int) $group->sum(fn (TeacherAttendance $row) => $row->taughtMinutes()),
                        'sessions' => $group->count(),
                    ];
                })
                ->all(),
            'totalMinutes' => (int) $attendances->sum(fn (TeacherAttendance $row) => $row->taughtMinutes()),
        ];
    }
}
