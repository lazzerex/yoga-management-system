<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class SyncUserProfileAction
{
    public function ensureRoleCanChange(User $user, string $role): void
    {
        if ($user->role === $role) {
            return;
        }

        $key = match ($this->profileWithHistory($user)) {
            'coach' => 'flash.cannotChangeRoleHasCoachProfile',
            'member' => 'flash.cannotChangeRoleHasStudentProfile',
            default => null,
        };

        if ($key) {
            throw ValidationException::withMessages(['role' => __($key)]);
        }
    }

    public function profileWithHistory(User $user): ?string
    {
        $coachProfile = $user->coachProfile()->first();

        if ($coachProfile && (
            $coachProfile->classSchedules()->exists()
            || $coachProfile->classSessions()->exists()
            || $coachProfile->teacherAttendances()->exists()
            || $coachProfile->lessonPlans()->exists()
        )) {
            return 'coach';
        }

        $studentProfile = $user->studentProfile()->first();

        if ($studentProfile && (
            $studentProfile->enrollments()->exists()
            || $studentProfile->invoices()->exists()
        )) {
            return 'member';
        }

        return null;
    }

    public function execute(User $user): void
    {
        if ($user->role !== 'coach') {
            $user->coachProfile()->delete();
        }

        if ($user->role !== 'member') {
            $user->studentProfile()->delete();
        }

        if ($user->role === 'coach' && ! $user->coachProfile()->exists()) {
            $user->coachProfile()->create();
        }

        if ($user->role === 'member' && ! $user->studentProfile()->exists()) {
            $user->studentProfile()->create();
        }

        $user->unsetRelation('coachProfile')->unsetRelation('studentProfile');
    }
}
