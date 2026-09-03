<?php

namespace App\Modules\Operations\Media\Actions;

use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\Payment;
use App\Models\StudentProfile;
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
            $owner instanceof CoachProfile => $owner->user_id === $user->id || $user->can('operations.coaches.view'),
            $owner instanceof StudentProfile => $this->seesStudent($user, $owner),
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
            $owner instanceof CoachProfile => $user->can('operations.coaches.manage'),
            $owner instanceof StudentProfile => $user->can('operations.students.manage'),
            $owner instanceof LessonPlan => $this->ownsLessonPlan($user, $owner) && $owner->isEditable(),
            default => false,
        };
    }

    /** Mirrors StudentProfileController::index: without .any the coach sees only students booked into their own sessions. */
    private function seesStudent(User $user, StudentProfile $profile): bool
    {
        if ($profile->user_id === $user->id || $user->can('operations.students.view.any')) {
            return true;
        }

        $coachProfileId = $user->coachProfile?->id;

        return $user->can('operations.students.view')
            && $coachProfileId !== null
            && $profile->enrollments()
                ->where('status', 'booked')
                ->whereHas('classSession', fn ($s) => $s->where('coach_profile_id', $coachProfileId))
                ->exists();
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
