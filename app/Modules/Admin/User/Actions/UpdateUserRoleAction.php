<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateUserRoleAction
{
    public function __construct(private AuditUserAction $audit) {}

    public function execute(User $user, string $role, ?User $actor): void {
        $oldRole = $user->role;

        if ($oldRole !== $role) {
            if ($oldRole === 'coach' && $user->coachProfile()->exists()) {
                throw ValidationException::withMessages([
                    'role' => __('flash.cannotChangeRoleHasCoachProfile'),
                ]);
            }

            if ($oldRole === 'member' && $user->studentProfile()->exists()) {
                throw ValidationException::withMessages([
                    'role' => __('flash.cannotChangeRoleHasStudentProfile'),
                ]);
            }
        }

        $user->update(['role' => $role]);

        $this->audit->execute($actor, 'assign_role', $user, [
            'from' => $oldRole,
            'to' => $role,
        ]);
    }
}