<?php

namespace App\Modules\Operations\ClassType\Actions;

use App\Models\ClassType;

class UpdateClassTypeAction
{
    public function execute(ClassType $classType, array $validated): ClassType
    {
        $classType->update($validated);

        return $classType;
    }
}
