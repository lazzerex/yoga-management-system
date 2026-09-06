<?php

namespace App\Modules\Operations\ClassSession\Actions;

use App\Models\ClassSession;
use App\Notifications\ClassCancelledNotification;
use Illuminate\Support\Facades\Notification;

class UpdateClassSessionAction
{
    public function execute(ClassSession $classSession, array $validated): ClassSession
    {
        $wasCancelled = $classSession->status === 'cancelled';

        $classSession->update([...$validated, 'is_overridden' => true]);

        if (! $wasCancelled && $classSession->status === 'cancelled') {
            Notification::send($this->affected($classSession), new ClassCancelledNotification($classSession));
        }

        return $classSession;
    }

    private function affected(ClassSession $classSession): array
    {
        $members = $classSession->enrollments()
            ->where('status', 'booked')
            ->with('studentProfile.user')
            ->get()
            ->map(fn ($enrollment) => $enrollment->studentProfile?->user)
            ->all();

        return array_values(array_filter([...$members, $classSession->coachProfile?->user]));
    }
}
