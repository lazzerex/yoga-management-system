<?php

namespace App\Modules\Admin\User\Controllers;

use App\Modules\Admin\User\Actions\CreateUserAction;
use App\Modules\Admin\User\Actions\DeleteUserAction;
use App\Modules\Admin\User\Actions\UpdateUserAction;
use App\Modules\Admin\User\Actions\UpdateUserRoleAction;
use App\Modules\Admin\User\Requests\StoreUserRequest;
use App\Modules\Admin\User\Requests\UpdateUserRequest;
use App\Modules\Admin\User\Requests\UpdateUserRoleRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;

class UserController extends Controller
{

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

    public function index(): Response
    {
        $users = User::withMax('loginLogs as last_login', 'logged_in_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return inertia('Admin/Users/Index', [
            'users' => $users->through(fn(User $user) => [
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
            'endpoints' => [
                'create' => route('admin.users.create'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $validated = $request->validated();

        $action->execute($validated, $request->user());

        return back()->with('success', "User {$validated['name']} created.");
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $action->execute($user, $validated, $request->user());

        return back()->with('success', "User {$user->name} updated.");
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, UpdateUserRoleAction $action): RedirectResponse
    {
        $validated = $request->validated();

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $action->execute($user, $validated['role'], $request->user());

        return back()->with('success', "Role updated for {$user->name}.");
    }

    public function destroy(Request $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->isRemovingLastAdmin($user, 'member')) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $name = $user->name;
        $action->execute($user, $request->user());

        return back()->with('success', "User {$name} deleted.");
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
