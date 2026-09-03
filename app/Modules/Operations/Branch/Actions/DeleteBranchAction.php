<?php

namespace App\Modules\Operations\Branch\Actions;

use App\Models\Branch;
use Illuminate\Validation\ValidationException;

class DeleteBranchAction
{
    public function execute(Branch $branch): void
    {
        if ($branch->rooms()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.branchHasRooms'),
            ]);
        }

        if ($branch->lessonPlans()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.branchHasLessonPlans'),
            ]);
        }

        if ($branch->tuitionPlans()->exists() || $branch->invoices()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.branchHasInvoices'),
            ]);
        }

        $branch->delete();
    }
}
