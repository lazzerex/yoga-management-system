<?php

namespace App\Modules\Operations\ClassSchedule\Actions;

use App\Models\ClassSchedule;
use Illuminate\Validation\ValidationException;

class DeleteClassScheduleAction
{
    public function execute(ClassSchedule $classSchedule): void
    {
        if ($classSchedule->classSessions()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.scheduleHasSessions'),
            ]);
        }

        $classSchedule->delete();
    }
}
