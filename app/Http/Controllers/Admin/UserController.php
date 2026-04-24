<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

class UserController extends Controller {

    public function create(): Response
    {
        return inertia('Admin/Users/Create');
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
        ]);
    }

    public function index(): Response
    {
        $users = User::withMax('loginLogs as last_login', 'logged_in_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class, 'username')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', 'in:admin,coach,member'],
            'password' => ['required', 'confirmed', Password::default()],
        ]);

        $user = User::create($validated);

        $this->recordAudit($request, 'create_user', $user, [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return back()->with('success', "User {$validated['name']} created.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class, 'username')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,coach,member'],
            'password' => ['nullable', 'confirmed', Password::default()],
        ]);

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $nameOrEmailChanged = $user->name !== $validated['name'] || $user->email !== $validated['email'];
        $passwordChanged = !empty($validated['password']);
        $roleChanged = $user->role !== $validated['role'];
        $oldName = $user->name;
        $oldEmail = $user->email;
        $oldRole = $user->role;

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($nameOrEmailChanged) {
            $this->recordAudit($request, 'update_user_info', $user, [
                'from' => ['name' => $oldName, 'email' => $oldEmail],
                'to' => ['name' => $user->name, 'email' => $user->email],
            ]);
        }

        if ($passwordChanged) {
            $this->recordAudit($request, 'change_password', $user, []);
        }

        if ($roleChanged) {
            $this->recordAudit($request, 'assign_role', $user, [
                'from' => $oldRole,
                'to' => $validated['role'],
            ]);
        }

        return back()->with('success', "User {$user->name} updated.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,coach,member'],
        ]);

        if ($this->isSelfDemotion($request, $user, $validated['role'])) {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        if ($this->isRemovingLastAdmin($user, $validated['role'])) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        $this->recordAudit($request, 'assign_role', $user, [
            'from' => $oldRole,
            'to' => $validated['role'],
        ]);

        return back()->with('success', "Role updated for {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->isRemovingLastAdmin($user, 'member')) {
            return back()->with('error', 'At least one admin account is required.');
        }

        $name = $user->name;
        $this->recordAudit($request, 'remove_role', $user, ['role' => $user->role]);
        $user->delete();

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

    private function recordAudit(Request $request, string $action, User $subject, array $meta): void
    {
        AuditLog::create([
            'causer_id' => $request->user()?->id,
            'action' => $action,
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'meta' => $meta ?: null,
        ]);
    }
}
