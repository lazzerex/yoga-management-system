<?php

namespace App\Actions\Admin\User;

use App\Models\User;

class UpdateUserRoleAction 
{
    public function __construct(private AuditUserAction $audit) {}
    
    public function execute(User $user, string $role, ?User $actor): void {
        $oldRole = $user->role;
        $user->update(['role' => $role]);

        $this->audit->execute($actor, 'assign_role', $user, [
            'from' => $oldRole,
            'to' => $role,
        ]);
    }
}