<?php

namespace App\Modules\Operations\Branch\Actions;

use App\Models\Branch;

class DeleteBranchAction
{
    public function execute(Branch $branch): void
    {
        $branch->delete();
    }
}
