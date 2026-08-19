<?php

namespace App\Modules\Operations\ClassType\Actions;

use App\Models\ClassType;

class DeleteClassTypeAction
{
    public function execute(ClassType $classType): void
    {
        $classType->delete();
    }
}
