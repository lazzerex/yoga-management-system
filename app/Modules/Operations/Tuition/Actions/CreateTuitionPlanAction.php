<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\TuitionPlan;

class CreateTuitionPlanAction
{
    public function execute(array $validated): TuitionPlan
    {
        return TuitionPlan::create($validated);
    }
}
