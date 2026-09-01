<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;
use Illuminate\Validation\ValidationException;

class DeleteCoachProfileAction
{
    public function execute(CoachProfile $coachProfile): void
    {
        $inUse = $coachProfile->classSchedules()->exists()
            || $coachProfile->classSessions()->exists()
            || $coachProfile->teacherAttendances()->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'action' => __('flash.coachProfileInUse'),
            ]);
        }

        $coachProfile->delete();
    }
}
