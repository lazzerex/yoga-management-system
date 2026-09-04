<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\StudentAttendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Trends over time. The month-by-month tables for one coach or one student stay on
 * the attendance reports page; this view never repeats them.
 */
class AttendanceTrendsAction
{
    private const WEEKS = 12;

    public function execute(?int $branchId, ?int $coachProfileId): array
    {
        $from = today()->startOfWeek()->subWeeks(self::WEEKS - 1);

        $marks = StudentAttendance::with('enrollment.classSession.classType:id,name')
            ->whereHas('enrollment.classSession', fn ($q) => $q
                ->whereBetween('session_date', [$from->toDateString(), today()->toDateString()])
                ->when($branchId, fn ($b) => $b->where('branch_id', $branchId))
                ->when($coachProfileId, fn ($c) => $c->where('coach_profile_id', $coachProfileId)))
            ->get();

        return [
            'weeks' => self::WEEKS,
            'weekly' => $this->weekly($marks, $from),
            'byClassType' => $this->byClassType($marks),
            'totals' => [
                'marked' => $marks->count(),
                'attended' => $marks->whereIn('status', ['present', 'late'])->count(),
            ],
        ];
    }

    /** @param  Collection<int, StudentAttendance>  $marks */
    private function weekly(Collection $marks, Carbon $from): array
    {
        $byWeek = $marks->groupBy(fn (StudentAttendance $mark) => Carbon::parse($mark->enrollment->classSession->session_date)
            ->startOfWeek()
            ->toDateString());

        return collect(range(0, self::WEEKS - 1))
            ->map(function (int $offset) use ($byWeek, $from) {
                $week = $from->copy()->addWeeks($offset);
                $group = $byWeek[$week->toDateString()] ?? collect();
                $total = $group->count();
                $attended = $group->whereIn('status', ['present', 'late'])->count();

                return [
                    'week' => $week->format('d/m'),
                    'present' => $group->where('status', 'present')->count(),
                    'late' => $group->where('status', 'late')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'rate' => $total > 0 ? (int) round($attended / $total * 100) : 0,
                ];
            })
            ->all();
    }

    /** @param  Collection<int, StudentAttendance>  $marks */
    private function byClassType(Collection $marks): array
    {
        return $marks
            ->groupBy(fn (StudentAttendance $mark) => $mark->enrollment->classSession->class_type_id)
            ->map(fn (Collection $group) => [
                'name' => $group->first()->enrollment->classSession->classType->name,
                'marked' => $group->count(),
                'noShowRate' => (int) round($group->where('status', 'absent')->count() / $group->count() * 100),
            ])
            ->sortByDesc('noShowRate')
            ->values()
            ->all();
    }
}
