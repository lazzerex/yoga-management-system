<?php

namespace App\Modules\Operations\ClassSession\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Modules\Operations\ClassSession\Actions\CoachWeeklyScheduleAction;
use App\Modules\Operations\ClassSession\Actions\UpdateClassSessionAction;
use App\Modules\Operations\ClassSession\Requests\UpdateClassSessionRequest;
use App\Support\Table\SortsQueries;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Response;

class ClassSessionController extends Controller
{
    use SortsQueries;

    public function index(Request $request): Response
    {
        $branchId = $request->attributes->get('currentBranch')?->id;
        $classTypeId = $request->integer('class_type_id');
        $coachProfileId = $request->integer('coach_profile_id');
        $status = $request->string('status')->toString();
        $from = $this->validDate($request->string('from')->toString());
        $to = $this->validDate($request->string('to')->toString());

        $query = ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name'])
            ->when($from === null, fn ($q) => $q->upcoming())
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($classTypeId, fn ($q) => $q->where('class_type_id', $classTypeId))
            ->when($coachProfileId, fn ($q) => $q->where('coach_profile_id', $coachProfileId))
            ->when(in_array($status, ['scheduled', 'cancelled', 'done'], true), fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->where('session_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('session_date', '<=', $to));

        $sort = $this->applySort($query, $request, [
            'session_date' => 'session_date',
            'status' => ['scheduled', 'done', 'cancelled'],
        ], 'session_date', 'asc');

        $sessions = $query->orderBy('start_time')
            ->paginate(20, pageName: 'sessionsPage')
            ->withQueryString();

        $schedules = ClassSchedule::with(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(20, pageName: 'schedulesPage');

        $canManage = $request->user()->can('operations.sessions.manage');

        return inertia('Operations/Academy', [
            'canManage' => $canManage,
            'sessions' => $sessions->through(fn (ClassSession $session) => [
                'id' => $session->id,
                'session_date' => $session->session_date,
                'start_time' => substr($session->start_time, 0, 5),
                'end_time' => substr($session->end_time, 0, 5),
                'branch_name' => $session->branch->name,
                'room_name' => $session->room->name,
                'class_type_name' => $session->classType->name,
                'coach_name' => $session->coachProfile->user->name,
                'capacity' => $session->capacity,
                'status' => $session->status,
                'is_overridden' => $session->is_overridden,
            ]),
            'schedules' => $schedules->through(fn (ClassSchedule $schedule) => [
                'id' => $schedule->id,
                'branch_name' => $schedule->branch->name,
                'room_name' => $schedule->room->name,
                'class_type_name' => $schedule->classType->name,
                'coach_name' => $schedule->coachProfile->user->name,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr($schedule->start_time, 0, 5),
                'duration_minutes' => $schedule->duration_minutes,
                'capacity' => $schedule->capacity,
                'is_active' => $schedule->is_active,
            ]),
            'stats' => [
                'upcomingSessions' => ClassSession::upcoming()->where('status', 'scheduled')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
                'activeSchedules' => ClassSchedule::active()
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
            ],
            'filters' => [
                'class_type_id' => $classTypeId ?: '',
                'coach_profile_id' => $coachProfileId ?: '',
                'status' => $status,
                'from' => $from ?? '',
                'to' => $to ?? '',
            ] + $sort,
            'options' => [
                'classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name']),
                'coaches' => CoachProfile::active()->with('user:id,name')->get()
                    ->map(fn (CoachProfile $profile) => ['id' => $profile->id, 'name' => $profile->user->name])
                    ->sortBy('name')->values(),
            ],
            'endpoints' => [
                'createSchedule' => $canManage ? route('operations.class-schedules.create') : null,
                'generateSessions' => $canManage ? route('operations.class-schedules.generate-sessions') : null,
                'index' => route('operations.academy'),
            ],
        ]);
    }

    public function edit(ClassSession $classSession): Response
    {
        $classSession->load(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name']);

        return inertia('Operations/ClassSessions/Edit', [
            'classSession' => [
                'id' => $classSession->id,
                'branch_id' => $classSession->branch_id,
                'branch_name' => $classSession->branch->name,
                'room_id' => $classSession->room_id,
                'class_type_name' => $classSession->classType->name,
                'coach_profile_id' => $classSession->coach_profile_id,
                'session_date' => $classSession->session_date,
                'start_time' => substr($classSession->start_time, 0, 5),
                'capacity' => $classSession->capacity,
                'status' => $classSession->status,
                'is_overridden' => $classSession->is_overridden,
            ],
            'rooms' => Room::where('branch_id', $classSession->branch_id)->active()->orderBy('name')->get(['id', 'name', 'capacity']),
            'coachProfiles' => CoachProfile::with('user:id,name')->active()->get(['id', 'user_id'])
                ->map(fn (CoachProfile $c) => ['id' => $c->id, 'name' => $c->user->name]),
            'endpoints' => [
                'update' => route('operations.class-sessions.update', $classSession),
                'index' => route('operations.academy'),
            ],
        ]);
    }

    public function update(UpdateClassSessionRequest $request, ClassSession $classSession, UpdateClassSessionAction $action): RedirectResponse
    {
        $action->execute($classSession, $request->validated());

        return redirect()
            ->route('operations.academy')
            ->with('success', ['key' => 'flash.classSessionUpdated']);
    }

    public function myTeachingSchedule(Request $request, CoachWeeklyScheduleAction $schedule): Response
    {
        return inertia('Coach/MyTeachingSchedule', [
            'sessions' => $schedule->execute($request->user()->coachProfile),
        ]);
    }

    public function myClasses(Request $request): Response
    {
        $coachProfile = $request->user()->coachProfile;

        $schedules = $coachProfile
            ? ClassSchedule::with(['branch:id,name', 'classType:id,name'])
                ->where('coach_profile_id', $coachProfile->id)
                ->active()
                ->get()
            : collect();

        $rows = $schedules->map(function (ClassSchedule $schedule) {
            $nextSession = ClassSession::where('class_schedule_id', $schedule->id)
                ->where('status', 'scheduled')
                ->where('session_date', '>=', now()->toDateString())
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->withCount([
                    'enrollments as booked_count' => fn ($q) => $q->where('status', 'booked'),
                    'enrollments as waitlisted_count' => fn ($q) => $q->where('status', 'waitlisted'),
                ])
                ->first();

            return [
                'id' => $schedule->id,
                'name' => $schedule->classType->name,
                'branch' => $schedule->branch->name,
                'students' => $nextSession->booked_count ?? 0,
                'waitlist' => $nextSession->waitlisted_count ?? 0,
                'capacity' => $nextSession->capacity ?? $schedule->capacity,
            ];
        });

        $totalStudents = $rows->sum('students');
        $totalCapacity = $rows->sum('capacity');

        return inertia('Coach/MyClasses', [
            'classes' => $rows->map(fn ($row) => Arr::except($row, 'capacity')),
            'stats' => [
                'classesThisWeek' => $rows->count(),
                'totalStudents' => $totalStudents,
                'avgFillRate' => $totalCapacity > 0 ? (int) round($totalStudents / $totalCapacity * 100) : 0,
            ],
        ]);
    }

    /** A filter value that is not a date is ignored rather than reaching the query. */
    private function validDate(string $value): ?string
    {
        try {
            return $value === '' ? null : Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
