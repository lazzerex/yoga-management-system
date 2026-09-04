<?php

namespace App\Modules\Operations\Media\Actions;

use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A file is authorised by the record it hangs off, never by a flat permission:
 * operations.files.view alone would otherwise hand every coach every payment proof.
 */
class AuthorizeMediaAccessAction
{
    public function canDownload(User $user, Media $media): bool
    {
        $owner = $media->model;

        return match (true) {
            $owner instanceof User => $this->seesUser($user, $owner),
            $owner instanceof LessonPlan => $this->seesLessonPlan($user, $owner),
            $owner instanceof Payment => $user->can('operations.tuition.manage'),
            default => false,
        };
    }

    public function canDelete(User $user, Media $media): bool
    {
        $owner = $media->model;

        // A proof is the evidence behind a money row. Voiding the payment replaces deleting it.
        if ($owner instanceof Payment) {
            return false;
        }

        if ($user->can('operations.files.manage')) {
            return true;
        }

        return match (true) {
            $owner instanceof User => $this->manageUser($user, $owner),
            $owner instanceof LessonPlan => $this->ownsLessonPlan($user, $owner) && $owner->isEditable(),
            default => false,
        };
    }

    /** An avatar is readable by its owner, by whoever may see that person's directory entry. */
    private function seesUser(User $user, User $owner): bool
    {
        if ($owner->id === $user->id || $user->can('admin.users.view')) {
            return true;
        }

        if ($owner->coachProfile && $user->can('operations.coaches.view')) {
            return true;
        }

        $profile = $owner->studentProfile;

        if (! $profile) {
            return false;
        }

        if ($user->can('operations.students.view.any')) {
            return true;
        }

        // Mirrors StudentProfileController::index: without .any the coach sees only
        // students booked into their own sessions.
        $coachProfileId = $user->coachProfile?->id;

        return $user->can('operations.students.view')
            && $coachProfileId !== null
            && $profile->enrollments()
                ->where('status', 'booked')
                ->whereHas('classSession', fn ($s) => $s->where('coach_profile_id', $coachProfileId))
                ->exists();
    }

    private function manageUser(User $user, User $owner): bool
    {
        return $owner->id === $user->id
            || ($owner->coachProfile && $user->can('operations.coaches.manage'))
            || ($owner->studentProfile && $user->can('operations.students.manage'));
    }

    private function seesLessonPlan(User $user, LessonPlan $plan): bool
    {
        return $user->can('operations.plans.view.any')
            || $plan->coach_profile_id === $user->coachProfile?->id;
    }

    private function ownsLessonPlan(User $user, LessonPlan $plan): bool
    {
        return $user->can('operations.plans.manage')
            && $plan->coach_profile_id === $user->coachProfile?->id;
    }
}
