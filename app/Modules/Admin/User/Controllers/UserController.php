<?php

namespace App\Modules\Admin\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\User\Actions\CreateUserAction;
use App\Modules\Admin\User\Actions\DeleteUserAction;
use App\Modules\Admin\User\Actions\UpdateUserAction;
use App\Modules\Admin\User\Actions\UpdateUserRoleAction;
use App\Modules\Admin\User\Requests\StoreUserRequest;
use App\Modules\Admin\User\Requests\UpdateUserRequest;
use App\Modules\Admin\User\Requests\UpdateUserRoleRequest;
use App\Support\Table\SortsQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class UserController extends Controller
{
    use SortsQueries;

    public function create(): Response
    {
        return inertia('Admin/Users/Create', [
            'endpoints' => [
                'store' => route('admin.users.store'),
                'index' => route('admin.users.index'),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        return inertia('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'endpoints' => [
                'update' => route('admin.users.update', $user),
                'index' => route('admin.users.index'),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $role = $request->string('role')->toString();

        $query = User::withMax('loginLogs as last_login', 'logged_in_at')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($role, ['admin', 'coach', 'member'], true), fn ($q) => $q->where('role', $role));

        $sort = $this->applySort($query, $request, [
            'name' => 'name',
            'username' => 'username',
            'role' => 'role',
            'created_at' => 'created_at',
            'last_login' => 'last_login',
        ], 'created_at');

        $users = $query->paginate(20)->withQueryString();

        return inertia('Admin/Users/Index', [
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->toDateString(),
                'last_login' => $user->last_login
                    ? Carbon::parse($user->last_login)->format('Y-m-d H:i')
                    : null,
            ]),
            'filters' => ['search' => $search, 'role' => $role] + $sort,
            'endpoints' => [
                'create' => route('admin.users.create'),
                'index' => route('admin.users.index'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $validated = $request->validated();

        $action->execute($validated, $request->user());

        return back()->with('success', ['key' => 'flash.userCreated', 'params' => ['name' => $validated['name']]]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return $this->selfDemotionError();
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return $this->lastAdminRequiredError();
        }

        $action->execute($user, $validated, $request->user());

        return back()->with('success', ['key' => 'flash.userUpdated', 'params' => ['name' => $user->name]]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, UpdateUserRoleAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return $this->selfDemotionError();
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return $this->lastAdminRequiredError();
        }

        $action->execute($user, $validated['role'], $request->user());

        return back()->with('success', ['key' => 'flash.roleUpdated', 'params' => ['name' => $user->name]]);
    }

    public function destroy(Request $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        // Validation errors, not flash: a flash redirect reads as success and closes the dialog.
        if ($request->user()?->is($user)) {
            $this->deleteBlocked('flash.cannotDeleteOwnAccount');
        }

        if ($this->isRemovingLastAdmin($user, 'member')) {
            $this->deleteBlocked('flash.lastAdminRequired');
        }

        if ($user->coachProfile) {
            $this->deleteBlocked('flash.cannotDeleteUserHasCoachProfile');
        }

        if ($user->studentProfile) {
            $this->deleteBlocked('flash.cannotDeleteUserHasStudentProfile');
        }

        $name = $user->name;
        $action->execute($user, $request->user());

        return back()->with('success', ['key' => 'flash.userDeleted', 'params' => ['name' => $name]]);
    }

    private function deleteBlocked(string $key): never
    {
        throw ValidationException::withMessages(['action' => __($key)]);
    }

    private function selfDemotionError(): RedirectResponse
    {
        return back()->with('error', ['key' => 'flash.cannotRemoveOwnRole']);
    }

    private function lastAdminRequiredError(): RedirectResponse
    {
        return back()->with('error', ['key' => 'flash.lastAdminRequired']);
    }

    private function isSelfDemotion(Request $request, User $targetUser, string $newRole): bool
    {
        return $request->user()?->is($targetUser) && $newRole !== 'admin';
    }

    private function isRemovingLastAdmin(User $targetUser, string $newRole): bool
    {
        if ($targetUser->role !== 'admin' || $newRole === 'admin') {
            return false;
        }

        return User::where('role', 'admin')->count() <= 1;
    }
}
