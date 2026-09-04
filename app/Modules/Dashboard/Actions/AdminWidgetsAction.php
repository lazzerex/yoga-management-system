<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\ClassSession;
use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Operations\Attendance\Actions\SessionBoardStatsAction;
use App\Modules\Operations\Tuition\Actions\TuitionStatsAction;

/**
 * Every widget is gated on the permission of the module that owns its data, so an
 * admin who cannot open the tuition board cannot read its figures here either.
 */
class AdminWidgetsAction
{
    public function __construct(
        private TuitionStatsAction $tuitionStats,
        private SessionBoardStatsAction $sessionStats,
    ) {}

    public function execute(User $user, ?int $branchId): array
    {
        $widgets = [];

        if ($user->can('operations.tuition.view')) {
            $widgets['money'] = $this->tuitionStats->execute($branchId) + [
                'url' => route('operations.tuition-fees'),
            ];
            $widgets['revenue'] = $this->revenue($branchId);
        }

        if ($user->can('operations.attendance.view')) {
            $widgets['todaySessions'] = $this->sessionStats->execute(today()->toDateString(), $branchId, null) + [
                'url' => route('operations.teacher-attendance'),
            ];
        }

        if ($user->can('operations.sessions.view')) {
            $widgets['timeline'] = $this->timeline($branchId);
            $widgets['occupancy'] = $this->occupancy($branchId);
        }

        if ($user->can('operations.plans.review')) {
            $widgets['pendingPlans'] = $this->pendingPlans($branchId);
        }

        if ($user->can('operations.students.view.any')) {
            $widgets['newStudents'] = $this->newStudents();
        }

        return $widgets;
    }

    /** Grouped in PHP rather than SQL: the test suite runs on sqlite and the app on MySQL. */
    private function revenue(?int $branchId): array
    {
        $from = now()->startOfMonth()->subMonths(5);

        $totals = Payment::recorded()
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('branch_id', $branchId)))
            ->where('paid_at', '>=', $from)
            ->get(['paid_at', 'amount'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments) => (int) $payments->sum('amount'));

        return [
            'months' => collect(range(0, 5))
                ->map(function (int $offset) use ($from, $totals) {
                    $month = $from->copy()->addMonths($offset);

                    return [
                        'month' => $month->format('Y-m'),
                        'amount' => $totals[$month->format('Y-m')] ?? 0,
                    ];
                })
                ->all(),
        ];
    }

    private function timeline(?int $branchId): array
    {
        return ClassSession::with(['classType:id,name', 'room:id,name', 'coachProfile.user:id,name'])
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->where('session_date', today()->toDateString())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'class_type_name' => $session->classType->name,
                'coach_name' => $session->coachProfile->user->name,
                'room_name' => $session->room->name,
                'status' => $session->status,
                'booked' => $session->booked_count,
                'capacity' => $session->capacity,
            ])
            ->all();
    }

    private function occupancy(?int $branchId): array
    {
        $sessions = ClassSession::withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->where('status', 'scheduled')
            ->whereBetween('session_date', [today()->toDateString(), today()->addDays(6)->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'capacity']);

        $capacity = (int) $sessions->sum('capacity');
        $booked = (int) $sessions->sum('booked_count');

        return [
            'sessions' => $sessions->count(),
            'booked' => $booked,
            'capacity' => $capacity,
            'rate' => $capacity > 0 ? (int) round($booked / $capacity * 100) : 0,
        ];
    }

    private function pendingPlans(?int $branchId): array
    {
        $scope = fn ($query) => $query->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        return [
            'count' => LessonPlan::query()->tap($scope)->count(),
            'items' => LessonPlan::with(['classType:id,name', 'coachProfile.user:id,name'])
                ->tap($scope)
                ->orderBy('submitted_at')
                ->limit(5)
                ->get()
                ->map(fn (LessonPlan $plan) => [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'coach_name' => $plan->coachProfile->user->name,
                    'class_type_name' => $plan->classType->name,
                    'submitted_at' => $plan->submitted_at?->toDateString(),
                    'showUrl' => route('operations.lesson-plans.show', $plan->id),
                ])
                ->all(),
            'url' => route('operations.lesson-plans.pending'),
        ];
    }

    /** Student profiles carry no branch, so this figure is centre-wide on every branch. */
    private function newStudents(): array
    {
        $startOfMonth = now()->startOfMonth();

        return [
            'count' => StudentProfile::where('created_at', '>=', $startOfMonth)->count(),
            'previous' => StudentProfile::whereBetween('created_at', [
                $startOfMonth->copy()->subMonth(),
                $startOfMonth->copy()->subSecond(),
            ])->count(),
            'total' => StudentProfile::active()->count(),
            'url' => route('operations.students.index'),
        ];
    }
}
