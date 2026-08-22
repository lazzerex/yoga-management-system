<?php

namespace App\Modules\Operations\ClassSession\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Modules\Operations\ClassSession\Actions\UpdateClassSessionAction;
use App\Modules\Operations\ClassSession\Requests\UpdateClassSessionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ClassSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $branchId = $request->integer('branch_id') ?: null;

        $sessions = ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name', 'coachProfile.user:id,name'])
            ->upcoming()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate(20, pageName: 'sessionsPage');

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
            'branchOptions' => Branch::orderBy('name')->get(['id', 'name']),
            'selectedBranchId' => $branchId,
            'endpoints' => [
                'createSchedule' => $canManage ? route('operations.class-schedules.create', $branchId ? ['branch_id' => $branchId] : []) : null,
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
            'rooms' => Room::where('branch_id', $classSession->branch_id)->active()->orderBy('name')->get(['id', 'name']),
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

    public function myTeachingSchedule(Request $request): Response
    {
        $coachProfile = $request->user()->coachProfile;

        $sessions = $coachProfile
            ? ClassSession::with(['branch:id,name', 'room:id,name', 'classType:id,name'])
                ->where('coach_profile_id', $coachProfile->id)
                ->where('status', '!=', 'cancelled')
                ->whereBetween('session_date', [now()->toDateString(), now()->addDays(6)->toDateString()])
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get()
            : collect();

        return inertia('Coach/MyTeachingSchedule', [
            'sessions' => $sessions->map(fn (ClassSession $session) => [
                'id' => $session->id,
                'session_date' => $session->session_date,
                'start_time' => substr($session->start_time, 0, 5),
                'class_type_name' => $session->classType->name,
                'branch_name' => $session->branch->name,
                'room_name' => $session->room->name,
            ]),
        ]);
    }
}
