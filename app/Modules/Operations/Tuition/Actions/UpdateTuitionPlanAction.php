<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\TuitionPlan;

class UpdateTuitionPlanAction
{
    public function execute(TuitionPlan $tuitionPlan, array $validated): TuitionPlan
    {
        $tuitionPlan->update($validated);

        return $tuitionPlan;
    }
}
