<?php

namespace App\Modules\Admin\User\Actions;

use App\Models\AuditLog;
use App\Models\User;

class AuditUserAction
{
    public function execute(?User $causer, string $action, User $subject, array $meta = []): void
    {
        AuditLog::create([
            'causer_id' => $causer?->id,
            'action' => $action,
            'subject_id' => $subject->id,
            'subject_name' => $subject->name,
            'meta' => $meta ?: null,
        ]);
    }
}