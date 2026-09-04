<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\StudentAttendance;
use Illuminate\Support\Carbon;

/** A member's own attendance over time; nothing here reaches another student's record. */
class MyProgressAction
{
    private const WEEKS = 12;

    public function execute(?int $studentProfileId): array
    {
        if (! $studentProfileId) {
            return ['weeks' => self::WEEKS, 'weekly' => [], 'split' => [], 'totals' => ['marked' => 0, 'attended' => 0]];
        }

        $from = today()->startOfWeek()->subWeeks(self::WEEKS - 1);

        $marks = StudentAttendance::with('enrollment.classSession:id,session_date')
            ->whereHas('enrollment', fn ($q) => $q->where('student_profile_id', $studentProfileId))
            ->whereHas('enrollment.classSession', fn ($q) => $q
                ->whereBetween('session_date', [$from->toDateString(), today()->toDateString()]))
            ->get();

        $byWeek = $marks->groupBy(fn (StudentAttendance $mark) => Carbon::parse($mark->enrollment->classSession->session_date)
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
                        'attended' => $group->whereIn('status', ['present', 'late'])->count(),
                        'absent' => $group->where('status', 'absent')->count(),
                    ];
                })
                ->all(),
            'split' => collect(StudentAttendance::STATUSES)
                ->map(fn (string $status) => ['status' => $status, 'count' => $marks->where('status', $status)->count()])
                ->all(),
            'totals' => [
                'marked' => $marks->count(),
                'attended' => $marks->whereIn('status', ['present', 'late'])->count(),
            ],
        ];
    }
}
