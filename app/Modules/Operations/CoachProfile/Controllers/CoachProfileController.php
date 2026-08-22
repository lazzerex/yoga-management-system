<?php

namespace App\Modules\Operations\CoachProfile\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\User;
use App\Modules\Operations\CoachProfile\Actions\CreateCoachProfileAction;
use App\Modules\Operations\CoachProfile\Actions\DeleteCoachProfileAction;
use App\Modules\Operations\CoachProfile\Actions\UpdateCoachProfileAction;
use App\Modules\Operations\CoachProfile\Requests\StoreCoachProfileRequest;
use App\Modules\Operations\CoachProfile\Requests\UpdateCoachProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CoachProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $profiles = CoachProfile::with(['user:id,name,username', 'classTypes:id,name'])
            ->orderBy('id')
            ->paginate(20);
        $canManage = $request->user()->can('operations.coaches.manage');

        return inertia('Operations/CoachProfiles/Index', [
            'canManage' => $canManage,
            'profiles' => $profiles->through(fn (CoachProfile $profile) => [
                'id' => $profile->id,
                'user_name' => $profile->user->name,
                'years_experience' => $profile->years_experience,
                'class_types' => $profile->classTypes->pluck('name'),
                'is_active' => $profile->is_active,
            ]),
            'stats' => [
                'total' => CoachProfile::count(),
                'active' => CoachProfile::active()->count(),
            ],
            'endpoints' => [
                'create' => $canManage ? route('operations.coaches.create') : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Operations/CoachProfiles/Create', [
            'users' => User::where('role', 'coach')->whereDoesntHave('coachProfile')->orderBy('name')->get(['id', 'name']),
            'classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name']),
            'endpoints' => [
                'store' => route('operations.coaches.store'),
                'index' => route('operations.coaches.index'),
            ],
        ]);
    }

    public function edit(CoachProfile $coachProfile): Response
    {
        $coachProfile->load(['user:id,name', 'classTypes:id']);

        return inertia('Operations/CoachProfiles/Edit', [
            'coachProfile' => [
                'id' => $coachProfile->id,
                'user_name' => $coachProfile->user->name,
                'bio' => $coachProfile->bio,
                'years_experience' => $coachProfile->years_experience,
                'certifications' => $coachProfile->certifications,
                'class_type_ids' => $coachProfile->classTypes->pluck('id'),
                'is_active' => $coachProfile->is_active,
            ],
            'classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name']),
            'endpoints' => [
                'update' => route('operations.coaches.update', $coachProfile),
                'index' => route('operations.coaches.index'),
            ],
        ]);
    }

    public function store(StoreCoachProfileRequest $request, CreateCoachProfileAction $action): RedirectResponse
    {
        $profile = $action->execute($request->validated());

        return redirect()
            ->route('operations.coaches.index')
            ->with('success', ['key' => 'flash.coachProfileCreated', 'params' => ['name' => $profile->user->name]]);
    }

    public function update(UpdateCoachProfileRequest $request, CoachProfile $coachProfile, UpdateCoachProfileAction $action): RedirectResponse
    {
        $action->execute($coachProfile, $request->validated());

        return redirect()
            ->route('operations.coaches.index')
            ->with('success', ['key' => 'flash.coachProfileUpdated', 'params' => ['name' => $coachProfile->user->name]]);
    }

    public function destroy(CoachProfile $coachProfile, DeleteCoachProfileAction $action): RedirectResponse
    {
        $name = $coachProfile->user->name;
        $action->execute($coachProfile);

        return back()->with('success', ['key' => 'flash.coachProfileDeleted', 'params' => ['name' => $name]]);
    }
}
