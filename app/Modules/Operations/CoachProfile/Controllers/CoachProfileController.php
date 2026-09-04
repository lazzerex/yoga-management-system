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
        $search = $request->string('search')->toString();
        $classTypeId = $request->integer('class_type_id');
        $status = $request->string('status')->toString();

        $profiles = CoachProfile::with(['user:id,name,username', 'user.media', 'classTypes:id,name'])
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")))
            ->when($classTypeId, fn ($q) => $q->whereHas('classTypes', fn ($c) => $c->where('class_types.id', $classTypeId)))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($q) => $q->where('is_active', $status === 'active'))
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();
        $canManage = $request->user()->can('operations.coaches.manage');

        return inertia('Operations/CoachProfiles/Index', [
            'canManage' => $canManage,
            'profiles' => $profiles->through(fn (CoachProfile $profile) => [
                'id' => $profile->id,
                'user_name' => $profile->user->name,
                'years_experience' => $profile->years_experience,
                'class_types' => $profile->classTypes->pluck('name'),
                'avatar_url' => $this->avatarUrl($profile),
                'is_active' => $profile->is_active,
            ]),
            'stats' => [
                'total' => CoachProfile::count(),
                'active' => CoachProfile::active()->count(),
            ],
            'filters' => ['search' => $search, 'class_type_id' => $classTypeId ?: '', 'status' => $status],
            'options' => ['classTypes' => ClassType::active()->orderBy('name')->get(['id', 'name'])],
            'endpoints' => [
                'create' => $canManage ? route('operations.coaches.create') : null,
                'index' => route('operations.coaches.index'),
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
        $coachProfile->load(['user:id,name', 'user.media', 'classTypes:id']);

        return inertia('Operations/CoachProfiles/Edit', [
            'coachProfile' => [
                'id' => $coachProfile->id,
                'user_name' => $coachProfile->user->name,
                'bio' => $coachProfile->bio,
                'years_experience' => $coachProfile->years_experience,
                'certifications' => $coachProfile->certifications,
                'class_type_ids' => $coachProfile->classTypes->pluck('id'),
                'is_active' => $coachProfile->is_active,
                'avatar_url' => $this->avatarUrl($coachProfile),
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

    private function avatarUrl(CoachProfile $profile): ?string
    {
        $avatar = $profile->user->getFirstMedia('avatar');

        return $avatar ? route('operations.files.show', [$avatar, 'conversion' => 'thumb']) : null;
    }

    public function destroy(CoachProfile $coachProfile, DeleteCoachProfileAction $action): RedirectResponse
    {
        $name = $coachProfile->user->name;
        $action->execute($coachProfile);

        return back()->with('success', ['key' => 'flash.coachProfileDeleted', 'params' => ['name' => $name]]);
    }
}
