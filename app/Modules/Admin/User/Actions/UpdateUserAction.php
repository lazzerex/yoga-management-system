<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function __construct(
        private AuditUserAction $audit,
        private SyncUserProfileAction $profiles,
    ) {}

    public function execute(User $user, array $validated, ?User $actor): void
    {
        $this->profiles->ensureRoleCanChange($user, $validated['role']);

        $infoChanged = $user->name !== $validated['name']
        || $user->email !== $validated['email']
        || $user->username !== $validated['username'];

        $passwordChanged = ! empty($validated['password']);
        $roleChanged = $user->role !== $validated['role'];
        $oldName = $user->name;
        $oldEmail = $user->email;
        $oldRole = $user->role;
        $oldUsername = $user->username;

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        DB::transaction(function () use ($user, $validated, $roleChanged) {
            $user->update($validated);

            if ($roleChanged) {
                $this->profiles->execute($user);
            }
        });

        if ($infoChanged) {
            $this->audit->execute($actor, 'update_user_info', $user, [
                'from' => ['name' => $oldName, 'email' => $oldEmail, 'username' => $oldUsername],
                'to' => ['name' => $user->name, 'email' => $user->email, 'username' => $user->username],
            ]);
        }

        if ($passwordChanged) {
            $this->audit->execute($actor, 'change_password', $user, []);
        }

        if ($roleChanged) {
            $this->audit->execute($actor, 'assign_role', $user, [
                'from' => $oldRole,
                'to' => $validated['role'],
            ]);
        }
    }
}
