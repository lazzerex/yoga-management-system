<?php

namespace App\Modules\Operations\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\User;
use App\Modules\Operations\Attendance\Actions\CheckInCoachAction;
use App\Modules\Operations\Attendance\Actions\CheckOutCoachAction;
use App\Modules\Operations\Attendance\Actions\MarkStudentAttendanceAction;
use App\Modules\Operations\Attendance\Actions\SessionBoardStatsAction;
use App\Modules\Operations\Attendance\Requests\MarkAttendanceRequest;
use App\Support\Pdf\DocumentPdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, SessionBoardStatsAction $stats): Response
    {
        $user = $request->user();
        $date = $this->resolveDate($request->string('date')->toString());
        $branchId = $request->attributes->get('currentBranch')?->id;
        $coachProfileId = $request->integer('coach_profile_id');
        $status = $request->string('status')->toString();

        $sessions = ClassSession::with([
            'branch:id,name',
            'room:id,name',
            'classType:id,name',
            'coachProfile:id,user_id',
            'coachProfile.user:id,name',
            'teacherAttendances',
        ])
            ->withCount($stats->rosterCounts())
            ->where('session_date', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(! $user->can('operations.attendance.manage.any'), fn ($q) => $q->where('coach_profile_id', $user->coachProfile?->id))
            ->when($coachProfileId, fn ($q) => $q->where('coach_profile_id', $coachProfileId))
            ->when(in_array($status, ['scheduled', 'cancelled', 'done'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('start_time')
            ->get();

        $rows = $sessions->map(function (ClassSession $session) use ($user) {
            $attendance = $session->teacherAttendances->firstWhere('coach_profile_id', $session->coach_profile_id);
            $canManage = $this->canManage($user, $session);

            return [
                'id' => $session->id,
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'class_type_name' => $session->classType->name,
                'coach_name' => $session->coachProfile->user->name,
                'branch_name' => $session->branch->name,
                'room_name' => $session->room->name,
                'status' => $session->status,
                'booked_count' => $session->booked_count,
                'marked_count' => $session->marked_count,
                'checked_in_at' => $attendance?->checked_in_at?->toIso8601String(),
                'checked_out_at' => $attendance?->checked_out_at?->toIso8601String(),
                'taught_minutes' => $attendance?->taughtMinutes() ?? 0,
                'rosterUrl' => route('operations.attendance.roster', $session->id),
                'checkInUrl' => $canManage && ! $attendance ? route('operations.attendance.check-in', $session->id) : null,
                'checkOutUrl' => $canManage && $attendance && ! $attendance->checked_out_at
                    ? route('operations.attendance.check-out', $session->id)
                    : null,
            ];
        });

        return inertia('Operations/TeacherAttendance', [
            'sessions' => $rows,
            'filters' => [
                'date' => $date,
                'coach_profile_id' => $coachProfileId ?: '',
                'status' => $status,
            ],
            'options' => [
                'coaches' => $user->can('operations.attendance.manage.any')
                    ? CoachProfile::active()->with('user:id,name')->get()
                        ->map(fn (CoachProfile $profile) => ['id' => $profile->id, 'name' => $profile->user->name])
                        ->sortBy('name')->values()
                    : [],
            ],
            'stats' => $stats->fromSessions($sessions),
            'endpoints' => [
                'reports' => route('operations.attendance.reports'),
                'index' => route('operations.teacher-attendance'),
            ],
        ]);
    }

    public function roster(Request $request, ClassSession $classSession): Response
    {
        $user = $request->user();
        abort_unless($this->canView($user, $classSession), 403);

        $classSession->load(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name']);

        $enrollments = $classSession->enrollments()
            ->with(['studentProfile.user:id,name', 'attendance'])
            ->where('status', 'booked')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->studentProfile->user->name)
            ->values();

        return inertia('Operations/Attendance/Roster', [
            'session' => [
                'id' => $classSession->id,
                'session_date' => $classSession->session_date,
                'start_time' => substr($classSession->start_time, 0, 5),
                'end_time' => substr($classSession->end_time, 0, 5),
                'class_type_name' => $classSession->classType->name,
                'coach_name' => $classSession->coachProfile->user->name,
                'branch_name' => $classSession->branch->name,
                'room_name' => $classSession->room->name,
                'status' => $classSession->status,
            ],
            'students' => $enrollments->map(fn ($enrollment) => [
                'enrollment_id' => $enrollment->id,
                'student_name' => $enrollment->studentProfile->user->name,
                'status' => $enrollment->attendance?->status,
                'notes' => $enrollment->attendance?->notes,
                'marked_at' => $enrollment->attendance?->marked_at?->toIso8601String(),
            ]),
            'canManage' => $this->canManage($user, $classSession),
            'endpoints' => [
                'mark' => route('operations.attendance.mark', $classSession->id),
                'back' => route('operations.teacher-attendance', ['date' => $classSession->session_date]),
            ],
        ]);
    }

    public function mark(MarkAttendanceRequest $request, ClassSession $classSession, MarkStudentAttendanceAction $action): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $classSession), 403);

        $action->execute($classSession, $request->validated()['entries'], $request->user());

        return back()->with('success', ['key' => 'flash.attendanceMarked']);
    }

    public function checkIn(Request $request, ClassSession $classSession, CheckInCoachAction $action): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $classSession), 403);

        $action->execute($classSession);

        return back()->with('success', ['key' => 'flash.attendanceCheckedIn']);
    }

    public function checkOut(Request $request, ClassSession $classSession, CheckOutCoachAction $action): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $classSession), 403);

        $action->execute($classSession);

        return back()->with('success', ['key' => 'flash.attendanceCheckedOut']);
    }

    public function reports(Request $request): Response
    {
        return inertia('Operations/Attendance/Reports', $this->reportData($request) + [
            'endpoints' => [
                'board' => route('operations.teacher-attendance'),
                'pdf' => route('operations.attendance.reports.pdf'),
            ],
        ]);
    }

    public function reportPdf(Request $request): \Illuminate\Http\Response
    {
        $data = $this->reportData($request);

        return DocumentPdf::download(
            'pdf.attendance-report',
            $data,
            __('pdf.reportTitle'),
            'attendance-'.$data['month'].'.pdf',
            __('pdf.reportPeriod', ['month' => $data['month']]),
        );
    }

    /**
     * @return array{month: string, taughtHours: Collection, attendanceRates: Collection}
     */
    private function reportData(Request $request): array
    {
        $user = $request->user();
        $branchId = $request->attributes->get('currentBranch')?->id;
        $month = $this->resolveMonth($request->string('month')->toString());
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $coachScopeId = $user->can('operations.attendance.manage.any') ? null : $user->coachProfile?->id;

        $taughtHours = TeacherAttendance::with(['coachProfile.user:id,name'])
            ->whereNotNull('checked_out_at')
            ->when($coachScopeId, fn ($q) => $q->where('coach_profile_id', $coachScopeId))
            ->whereHas('classSession', fn ($q) => $q
                ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId)))
            ->get()
            ->groupBy('coach_profile_id')
            ->map(fn ($rows) => [
                'coach_name' => $rows->first()->coachProfile->user->name,
                'sessions' => $rows->count(),
                'minutes' => $rows->sum(fn (TeacherAttendance $row) => $row->taughtMinutes()),
            ])
            ->sortByDesc('minutes')
            ->values();

        $attendanceRates = StudentAttendance::with(['enrollment.studentProfile.user:id,name'])
            ->whereHas('enrollment.classSession', fn ($q) => $q
                ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($coachScopeId, fn ($q) => $q->where('coach_profile_id', $coachScopeId)))
            ->get()
            ->groupBy(fn (StudentAttendance $row) => $row->enrollment->student_profile_id)
            ->map(function ($rows) {
                $total = $rows->count();
                $present = $rows->where('status', 'present')->count();
                $late = $rows->where('status', 'late')->count();

                return [
                    'student_name' => $rows->first()->enrollment->studentProfile->user->name,
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $rows->where('status', 'absent')->count(),
                    'rate' => (int) round(($present + $late) / $total * 100),
                ];
            })
            ->sortBy('student_name')
            ->values();

        return [
            'month' => $month,
            'taughtHours' => $taughtHours,
            'attendanceRates' => $attendanceRates,
        ];
    }

    private function canView(User $user, ClassSession $classSession): bool
    {
        return $user->can('operations.attendance.manage.any')
            || $classSession->coach_profile_id === $user->coachProfile?->id;
    }

    private function canManage(User $user, ClassSession $classSession): bool
    {
        return $user->can('operations.attendance.manage') && $this->canView($user, $classSession);
    }

    private function resolveDate(string $value): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function resolveMonth(string $value): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $value)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }
}
