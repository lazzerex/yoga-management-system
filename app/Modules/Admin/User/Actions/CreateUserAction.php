<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function __construct(
        private AuditUserAction $audit,
        private SyncUserProfileAction $profiles,
    ) {}

    public function execute(array $validated, ?User $actor): User
    {
        $user = DB::transaction(function () use ($validated) {
            $user = User::create($validated);

            $this->profiles->execute($user);

            return $user;
        });

        $this->audit->execute($actor, 'create_user', $user, [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return $user;
    }
}
