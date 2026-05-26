<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;

class DeleteUserAction 
{
    public function __construct(private AuditUserAction $audit) {}

    public function execute(User $user, ?User $actor): void {
        $this->audit->execute($actor, 'remove_role', $user, ['role' => $user->role]);
        $user->delete();
    }
}