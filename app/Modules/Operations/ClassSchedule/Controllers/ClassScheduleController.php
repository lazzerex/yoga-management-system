<?php

namespace App\Modules\Operations\ClassSchedule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Modules\Operations\ClassSchedule\Actions\CreateClassScheduleAction;
use App\Modules\Operations\ClassSchedule\Actions\DeleteClassScheduleAction;
use App\Modules\Operations\ClassSchedule\Actions\UpdateClassScheduleAction;
use App\Modules\Operations\ClassSchedule\Requests\StoreClassScheduleRequest;
use App\Modules\Operations\ClassSchedule\Requests\UpdateClassScheduleRequest;
use App\Modules\Operations\ClassSession\Actions\GenerateClassSessionsAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ClassScheduleController extends Controller
{
    public function create(Request $request): Response
    {
        return inertia('Operations/ClassSchedules/Create', [
            'selectedBranchId' => $request->attributes->get('currentBranch')?->id,
            ...$this->formProps(),
        ]);
    }

    public function edit(ClassSchedule $classSchedule): Response
    {
        return inertia('Operations/ClassSchedules/Edit', [
            'classSchedule' => [
                'id' => $classSchedule->id,
                'branch_id' => $classSchedule->branch_id,
                'room_id' => $classSchedule->room_id,
                'class_type_id' => $classSchedule->class_type_id,
                'coach_profile_id' => $classSchedule->coach_profile_id,
                'day_of_week' => $classSchedule->day_of_week,
                'start_time' => substr($classSchedule->start_time, 0, 5),
                'duration_minutes' => $classSchedule->duration_minutes,
                'capacity' => $classSchedule->capacity,
                'is_active' => $classSchedule->is_active,
            ],
            ...$this->formProps(),
            'endpoints' => [
                'update' => route('operations.class-schedules.update', $classSchedule),
                'index' => route('operations.academy'),
            ],
        ]);
    }

    public function store(StoreClassScheduleRequest $request, CreateClassScheduleAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()
            ->route('operations.academy')
            ->with('success', ['key' => 'flash.classScheduleCreated']);
    }

    public function update(UpdateClassScheduleRequest $request, ClassSchedule $classSchedule, UpdateClassScheduleAction $action): RedirectResponse
    {
        $action->execute($classSchedule, $request->validated());

        return redirect()
            ->route('operations.academy')
            ->with('success', ['key' => 'flash.classScheduleUpdated']);
    }

    public function destroy(ClassSchedule $classSchedule, DeleteClassScheduleAction $action): RedirectResponse
    {
        $action->execute($classSchedule);

        return back()->with('success', ['key' => 'flash.classScheduleDeleted']);
    }

    public function generateSessions(GenerateClassSessionsAction $action): RedirectResponse
    {
        $created = $action->execute();

        return redirect()
            ->route('operations.academy')
            ->with('success', ['key' => 'flash.sessionsGenerated', 'params' => ['count' => $created]]);
    }

    private function formProps(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
            'rooms' => Room::with('branch:id,name')->active()->orderBy('name')->get(['id', 'branch_id', 'name', 'capacity']),
            'classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name']),
            'coachProfiles' => CoachProfile::with('user:id,name')->active()->get(['id', 'user_id'])
                ->map(fn (CoachProfile $c) => ['id' => $c->id, 'name' => $c->user->name]),
            'endpoints' => [
                'store' => route('operations.class-schedules.store'),
                'index' => route('operations.academy'),
            ],
        ];
    }
}
