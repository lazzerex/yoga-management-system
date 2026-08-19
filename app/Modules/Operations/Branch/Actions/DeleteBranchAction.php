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
                'branch' => __('flash.branchHasRooms'),
            ]);
        }

        $branch->delete();
    }
}
