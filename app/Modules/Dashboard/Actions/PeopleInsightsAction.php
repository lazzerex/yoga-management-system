<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use Illuminate\Support\Collection;

/** Aggregates only: the coach and student directories own the record lists. */
class PeopleInsightsAction
{
    private const MONTHS = 12;

    /** Student profiles carry no branch, so student figures are centre-wide on every branch. */
    public function execute(?int $branchId): array
    {
        return [
            'growth' => $this->growth(),
            'activeSplit' => [
                'active' => StudentProfile::active()->count(),
                'inactive' => StudentProfile::where('is_active', false)->count(),
            ],
            'attendanceBuckets' => $this->attendanceBuckets($branchId),
            'coachLoad' => $this->coachLoad($branchId),
        ];
    }

    private function growth(): array
    {
        $from = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $byMonth = StudentProfile::where('created_at', '>=', $from)
            ->get(['created_at'])
            ->groupBy(fn (StudentProfile $profile) => $profile->created_at->format('Y-m'))
            ->map(fn (Collection $group) => $group->count());

        return collect(range(0, self::MONTHS - 1))
            ->map(function (int $offset) use ($from, $byMonth) {
                $month = $from->copy()->addMonths($offset)->format('Y-m');

                return ['month' => $month, 'count' => $byMonth[$month] ?? 0];
            })
            ->all();
    }

    /** How many students sit in each attendance band, over sessions marked in the last 90 days. */
    private function attendanceBuckets(?int $branchId): array
    {
        $rates = StudentAttendance::with('enrollment:id,student_profile_id')
            ->whereHas('enrollment.classSession', fn ($q) => $q
                ->where('session_date', '>=', today()->subDays(89)->toDateString())
                ->when($branchId, fn ($b) => $b->where('branch_id', $branchId)))
            ->get(['id', 'enrollment_id', 'status'])
            ->groupBy(fn (StudentAttendance $mark) => $mark->enrollment->student_profile_id)
            ->map(fn (Collection $marks) => $marks->whereIn('status', ['present', 'late'])->count() / $marks->count() * 100);

        $bands = [
            ['label' => '0-49%', 'min' => 0, 'max' => 49.999],
            ['label' => '50-69%', 'min' => 50, 'max' => 69.999],
            ['label' => '70-84%', 'min' => 70, 'max' => 84.999],
            ['label' => '85-94%', 'min' => 85, 'max' => 94.999],
            ['label' => '95-100%', 'min' => 95, 'max' => 100],
        ];

        return collect($bands)
            ->map(fn (array $band) => [
                'label' => $band['label'],
                'students' => $rates->filter(fn (float $rate) => $rate >= $band['min'] && $rate <= $band['max'])->count(),
            ])
            ->all();
    }

    private function coachLoad(?int $branchId): array
    {
        return TeacherAttendance::with('coachProfile.user:id,name')
            ->whereNotNull('checked_out_at')
            ->whereHas('classSession', fn ($q) => $q
                ->whereBetween('session_date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString(),
                ])
                ->when($branchId, fn ($b) => $b->where('branch_id', $branchId)))
            ->get()
            ->groupBy('coach_profile_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->coachProfile->user->name,
                'minutes' => (int) $rows->sum(fn (TeacherAttendance $row) => $row->taughtMinutes()),
                'sessions' => $rows->count(),
            ])
            ->sortByDesc('minutes')
            ->values()
            ->all();
    }
}
