<?php

namespace App\Modules\Operations\ClassSession\Actions;

use App\Models\ClassSession;

class UpdateClassSessionAction
{
    public function execute(ClassSession $classSession, array $validated): ClassSession
    {
        $classSession->update([...$validated, 'is_overridden' => true]);

        return $classSession;
    }
}
