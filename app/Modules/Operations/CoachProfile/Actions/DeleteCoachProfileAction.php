<?php

namespace App\Modules\Operations\CoachProfile\Actions;

use App\Models\CoachProfile;

class DeleteCoachProfileAction
{
    public function execute(CoachProfile $coachProfile): void
    {
        $coachProfile->delete();
    }
}
