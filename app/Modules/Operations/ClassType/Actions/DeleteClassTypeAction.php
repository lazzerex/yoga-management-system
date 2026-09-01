<?php

namespace App\Modules\Operations\ClassType\Actions;

use App\Models\ClassType;
use Illuminate\Validation\ValidationException;

class DeleteClassTypeAction
{
    public function execute(ClassType $classType): void
    {
        if ($classType->classSchedules()->exists() || $classType->classSessions()->exists() || $classType->lessonPlans()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.classTypeInUse'),
            ]);
        }

        $classType->delete();
    }
}
