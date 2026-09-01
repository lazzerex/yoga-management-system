<?php

namespace App\Modules\Operations\StudentProfile\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Admin\User\Actions\AuditUserAction;
use App\Modules\Operations\StudentProfile\Actions\CreateStudentProfileAction;
use App\Modules\Operations\StudentProfile\Actions\DeleteStudentProfileAction;
use App\Modules\Operations\StudentProfile\Actions\UpdateStudentProfileAction;
use App\Modules\Operations\StudentProfile\Requests\StoreStudentProfileRequest;
use App\Modules\Operations\StudentProfile\Requests\UpdateStudentProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class StudentProfileController extends Controller
{
    public function __construct(private AuditUserAction $audit) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $seesEveryone = $user->can('operations.students.view.any');
        $coachProfileId = $user->coachProfile?->id;

        // Without the .any permission the directory narrows to students booked into the viewer's own sessions.
        $scope = fn ($query) => $query->when(! $seesEveryone, fn ($q) => $q
            ->whereHas('enrollments', fn ($e) => $e
                ->where('status', 'booked')
                ->whereHas('classSession', fn ($s) => $s->where('coach_profile_id', $coachProfileId))));

        // medical_notes intentionally excluded from the list query, not just the response shape.
        $profiles = StudentProfile::with('user:id,name,username')
            ->select(['id', 'user_id', 'goals', 'is_active'])
            ->tap($scope)
            ->orderBy('id')
            ->paginate(20);
        $canManage = $user->can('operations.students.manage');

        return inertia('Operations/StudentProfiles/Index', [
            'canManage' => $canManage,
            'profiles' => $profiles->through(fn (StudentProfile $profile) => [
                'id' => $profile->id,
                'user_name' => $profile->user->name,
                'goals' => $profile->goals,
                'is_active' => $profile->is_active,
            ]),
            'stats' => [
                'total' => StudentProfile::query()->tap($scope)->count(),
                'active' => StudentProfile::active()->tap($scope)->count(),
            ],
            'endpoints' => [
                'create' => $canManage ? route('operations.students.create') : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Operations/StudentProfiles/Create', [
            'users' => User::where('role', 'member')->whereDoesntHave('studentProfile')->orderBy('name')->get(['id', 'name']),
            'canViewMedical' => request()->user()->can('operations.students.medical.view'),
            'endpoints' => [
                'store' => route('operations.students.store'),
                'index' => route('operations.students.index'),
            ],
        ]);
    }

    public function edit(Request $request, StudentProfile $studentProfile): Response
    {
        $studentProfile->load('user:id,name');
        $canViewMedical = $request->user()->can('operations.students.medical.view');

        if ($canViewMedical) {
            $this->audit->execute($request->user(), 'view_student_medical_notes', $studentProfile->user);
        }

        return inertia('Operations/StudentProfiles/Edit', [
            'studentProfile' => [
                'id' => $studentProfile->id,
                'user_name' => $studentProfile->user->name,
                'emergency_contact_name' => $studentProfile->emergency_contact_name,
                'emergency_contact_phone' => $studentProfile->emergency_contact_phone,
                'medical_notes' => $canViewMedical ? $studentProfile->medical_notes : null,
                'goals' => $studentProfile->goals,
                'is_active' => $studentProfile->is_active,
            ],
            'canViewMedical' => $canViewMedical,
            'endpoints' => [
                'update' => route('operations.students.update', $studentProfile),
                'index' => route('operations.students.index'),
            ],
        ]);
    }

    public function store(StoreStudentProfileRequest $request, CreateStudentProfileAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if (! $request->user()->can('operations.students.medical.view')) {
            unset($validated['medical_notes']);
        }

        $profile = $action->execute($validated);

        return redirect()
            ->route('operations.students.index')
            ->with('success', ['key' => 'flash.studentProfileCreated', 'params' => ['name' => $profile->user->name]]);
    }

    public function update(UpdateStudentProfileRequest $request, StudentProfile $studentProfile, UpdateStudentProfileAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if (! $request->user()->can('operations.students.medical.view')) {
            unset($validated['medical_notes']);
        }

        $action->execute($studentProfile, $validated);

        return redirect()
            ->route('operations.students.index')
            ->with('success', ['key' => 'flash.studentProfileUpdated', 'params' => ['name' => $studentProfile->user->name]]);
    }

    public function destroy(StudentProfile $studentProfile, DeleteStudentProfileAction $action): RedirectResponse
    {
        $name = $studentProfile->user->name;
        $action->execute($studentProfile);

        return back()->with('success', ['key' => 'flash.studentProfileDeleted', 'params' => ['name' => $name]]);
    }
}
