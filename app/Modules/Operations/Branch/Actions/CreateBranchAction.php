<?php

namespace App\Modules\Operations\Branch\Actions;

use App\Models\Branch;

class CreateBranchAction
{
    public function execute(array $validated): Branch
    {
        return Branch::create($validated);
    }
}
