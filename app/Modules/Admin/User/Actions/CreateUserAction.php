<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;

class CreateUserAction 
{
    public function __construct(private AuditUserAction $audit) {}

    public function execute(array $validated, ?User $actor): User {
        $user = User::create($validated);
        $this->audit->execute($actor, 'create_user', $user, [
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ]);
        
        return $user;
    }
}