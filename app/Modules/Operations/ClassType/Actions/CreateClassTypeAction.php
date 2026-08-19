<?php

namespace App\Modules\Operations\ClassType\Actions;

use App\Models\ClassType;

class CreateClassTypeAction
{
    public function execute(array $validated): ClassType
    {
        return ClassType::create($validated);
    }
}
