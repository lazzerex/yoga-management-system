<?php

namespace App\Actions\Admin\User;

use App\Models\User;

class UpdateUserAction 
{
    public function __construct(private AuditUserAction $audit) {}

    public function execute(User $user, array $validated, ?User $actor): void {
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
			$this->audit->execute($actor, 'update_user_info', $user, [
				'from' => ['name' => $oldName, 'email' => $oldEmail],
				'to' => ['name' => $user->name, 'email' => $user->email],
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