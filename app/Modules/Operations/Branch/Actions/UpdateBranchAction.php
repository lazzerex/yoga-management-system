<?php

namespace App\Modules\Operations\Branch\Actions;

use App\Models\Branch;

class UpdateBranchAction
{
    public function execute(Branch $branch, array $validated): Branch
    {
        $branch->update($validated);

        return $branch;
    }
}
