<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserRoleAction
{
    public function __construct(
        private AuditUserAction $audit,
        private SyncUserProfileAction $profiles,
    ) {}

    public function execute(User $user, string $role, ?User $actor): void
    {
        $oldRole = $user->role;

        $this->profiles->ensureRoleCanChange($user, $role);

        DB::transaction(function () use ($user, $role, $oldRole) {
            $user->update(['role' => $role]);

            if ($oldRole !== $role) {
                $this->profiles->execute($user);
            }
        });

        $this->audit->execute($actor, 'assign_role', $user, [
            'from' => $oldRole,
            'to' => $role,
        ]);
    }
}
