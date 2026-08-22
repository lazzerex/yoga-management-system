<?php

namespace App\Modules\Operations\ClassSchedule\Actions;

use App\Models\ClassSchedule;

class CreateClassScheduleAction
{
    public function execute(array $validated): ClassSchedule
    {
        return ClassSchedule::create($validated);
    }
}
