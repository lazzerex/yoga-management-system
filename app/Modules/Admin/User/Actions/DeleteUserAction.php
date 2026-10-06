<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;

class DeleteUserAction
{
    public function __construct(private AuditUserAction $audit) {}

    public function execute(User $user, ?User $actor): void
    {
        $role = $user->role;
        $user->delete();
        $this->audit->execute($actor, 'delete_user', $user, ['role' => $role]);
    }
}
