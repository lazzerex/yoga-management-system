<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The whole centre on one page. This is the only view that ignores the header branch
 * switcher on purpose: it compares branches, and carries its own branch filter so a
 * single one can still be singled out.
 */
class CentreOverviewAction
{
    public function execute(User $user, ?int $branchId, int $months): array
    {
        $branches = Branch::active()->orderBy('name')->get(['id', 'name']);
        $scope = $branchId ? $branches->where('id', $branchId) : $branches;
        $from = now()->startOfMonth()->subMonths($months - 1);

        $overview = [
            'branches' => $branches->map(fn (Branch $branch) => ['id' => $branch->id, 'name' => $branch->name])->all(),
            'branchCount' => $scope->count(),
        ];

        $sessions = $user->can('operations.sessions.view')
            ? $this->sessions($scope->pluck('id'))
            : collect();

        if ($user->can('operations.sessions.view')) {
            $overview['activity'] = $this->activity($sessions, $scope);
            $overview['today'] = $this->today($scope->pluck('id'));
            $overview['byClassType'] = $this->byClassType($scope->pluck('id'));
        }

        if ($user->can('operations.tuition.view')) {
            $overview['money'] = $this->money($scope, $from, $months);
        }

        if ($user->can('operations.attendance.view')) {
            $overview['attendance'] = $this->attendance($scope);
        }

        if ($user->can('operations.students.view.any')) {
            $overview['students'] = $this->students($scope);
        }

        return $overview;
    }

    /** Upcoming week, which is what "how full are we" means to whoever is looking. */
    private function sessions(Collection $branchIds): Collection
    {
        return ClassSession::withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'scheduled')
            ->whereBetween('session_date', [today()->toDateString(), today()->addDays(6)->toDateString()])
            ->get(['id', 'branch_id', 'capacity']);
    }

    private function activity(Collection $sessions, Collection $branches): array
    {
        $byBranch = $sessions->groupBy('branch_id');

        return [
            'sessions' => $sessions->count(),
            'booked' => (int) $sessions->sum('booked_count'),
            'capacity' => (int) $sessions->sum('capacity'),
            'rate' => $sessions->sum('capacity') > 0
                ? (int) round($sessions->sum('booked_count') / $sessions->sum('capacity') * 100)
                : 0,
            'byBranch' => $branches->map(function (Branch $branch) use ($byBranch) {
                $group = $byBranch[$branch->id] ?? collect();
                $capacity = (int) $group->sum('capacity');

                return [
                    'branch' => $branch->name,
                    'sessions' => $group->count(),
                    'booked' => (int) $group->sum('booked_count'),
                    'capacity' => $capacity,
                    'rate' => $capacity > 0 ? (int) round($group->sum('booked_count') / $capacity * 100) : 0,
                ];
            })->values()->all(),
        ];
    }

    private function today(Collection $branchIds): array
    {
        return ClassSession::with(['branch:id,name', 'classType:id,name', 'room:id,name', 'coachProfile.user:id,name'])
            ->withCount(['enrollments as booked_count' => fn ($q) => $q->where('status', 'booked')])
            ->whereIn('branch_id', $branchIds)
            ->where('session_date', today()->toDateString())
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'start_time' => substr($session->start_time, 0, 5),
                'branch_name' => $session->branch->name,
                'class_type_name' => $session->classType->name,
                'coach_name' => $session->coachProfile->user->name,
                'room_name' => $session->room->name,
                'status' => $session->status,
                'booked' => $session->booked_count,
                'capacity' => $session->capacity,
            ])
            ->all();
    }

    /** What the centre actually practises, by seats taken over the last 30 days. */
    private function byClassType(Collection $branchIds): array
    {
        return Enrollment::where('status', 'booked')
            ->whereHas('classSession', fn ($q) => $q
                ->whereIn('branch_id', $branchIds)
                ->where('session_date', '>=', today()->subDays(29)->toDateString()))
            ->with('classSession.classType:id,name')
            ->get(['id', 'class_session_id'])
            ->groupBy(fn (Enrollment $enrollment) => $enrollment->classSession->classType->name)
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->map(fn (int $bookings, string $name) => ['name' => $name, 'bookings' => $bookings])
            ->values()
            ->all();
    }

    /** Revenue is grouped in PHP: the suite runs on sqlite and the app on MySQL. */
    private function money(Collection $branches, Carbon $from, int $months): array
    {
        $branchIds = $branches->pluck('id');

        $payments = Payment::recorded()
            ->whereHas('invoice', fn ($q) => $q->whereIn('branch_id', $branchIds))
            ->where('paid_at', '>=', $from)
            ->with('invoice:id,branch_id')
            ->get(['id', 'invoice_id', 'amount', 'paid_at']);

        $open = Invoice::whereIn('branch_id', $branchIds)
            ->open()
            ->withSum('recordedPayments', 'amount')
            ->get(['id', 'branch_id', 'total_amount', 'due_date']);

        $monthKeys = collect(range(0, $months - 1))
            ->map(fn (int $offset) => $from->copy()->addMonths($offset)->format('Y-m'));

        $byBranchMonth = $payments->groupBy(fn (Payment $payment) => $payment->invoice->branch_id.'|'.$payment->paid_at->format('Y-m'))
            ->map(fn (Collection $group) => (int) $group->sum('amount'));

        return [
            'collectedThisMonth' => (int) $payments
                ->filter(fn (Payment $payment) => $payment->paid_at->isSameMonth(now()))
                ->sum('amount'),
            'collectedInWindow' => (int) $payments->sum('amount'),
            'outstanding' => (int) $open->sum(fn (Invoice $invoice) => $invoice->balance()),
            'overdueCount' => $open->filter(fn (Invoice $invoice) => $invoice->isOverdue())->count(),
            'months' => $monthKeys->all(),
            'series' => $branches->map(fn (Branch $branch) => [
                'branch' => $branch->name,
                'amounts' => $monthKeys->map(fn (string $month) => $byBranchMonth[$branch->id.'|'.$month] ?? 0)->all(),
            ])->values()->all(),
            'byBranch' => $branches->map(function (Branch $branch) use ($open, $payments) {
                $branchOpen = $open->where('branch_id', $branch->id);

                return [
                    'branch' => $branch->name,
                    'outstanding' => (int) $branchOpen->sum(fn (Invoice $invoice) => $invoice->balance()),
                    'collected' => (int) $payments
                        ->filter(fn (Payment $payment) => $payment->invoice->branch_id === $branch->id)
                        ->sum('amount'),
                ];
            })->values()->all(),
        ];
    }

    private function attendance(Collection $branches): array
    {
        $marks = StudentAttendance::whereHas('enrollment.classSession', fn ($q) => $q
            ->whereIn('branch_id', $branches->pluck('id'))
            ->where('session_date', '>=', today()->subDays(29)->toDateString()))
            ->with('enrollment.classSession:id,branch_id')
            ->get(['id', 'enrollment_id', 'status']);

        $rate = fn (Collection $rows) => $rows->count() > 0
            ? (int) round($rows->whereIn('status', ['present', 'late'])->count() / $rows->count() * 100)
            : 0;

        return [
            'marked' => $marks->count(),
            'rate' => $rate($marks),
            'byBranch' => $branches->map(fn (Branch $branch) => [
                'branch' => $branch->name,
                'rate' => $rate($marks->filter(fn (StudentAttendance $mark) => $mark->enrollment->classSession->branch_id === $branch->id)),
            ])->values()->all(),
        ];
    }

    /** Student profiles carry no branch, so "active here" means booked into this branch. */
    private function students(Collection $branches): array
    {
        $bookedPerBranch = Enrollment::where('status', 'booked')
            ->whereHas('classSession', fn ($q) => $q
                ->whereIn('branch_id', $branches->pluck('id'))
                ->where('session_date', '>=', today()->subDays(29)->toDateString()))
            ->with('classSession:id,branch_id')
            ->get(['id', 'student_profile_id', 'class_session_id'])
            ->groupBy(fn (Enrollment $enrollment) => $enrollment->classSession->branch_id)
            ->map(fn (Collection $group) => $group->pluck('student_profile_id')->unique()->count());

        return [
            'active' => StudentProfile::active()->count(),
            'newThisMonth' => StudentProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'bookingRecently' => (int) $bookedPerBranch->sum(),
            'byBranch' => $branches->map(fn (Branch $branch) => [
                'branch' => $branch->name,
                'students' => $bookedPerBranch[$branch->id] ?? 0,
            ])->values()->all(),
        ];
    }
}
