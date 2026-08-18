<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Downtown Studio', 'address' => '1 Main St', 'phone' => '0901234567'],
            ['name' => 'Westside Branch', 'address' => '28 Lake Ave', 'phone' => '0901234568'],
            ['name' => 'Riverside Center', 'address' => '19 River Blvd', 'phone' => '0901234569'],
            ['name' => 'Uptown Loft', 'address' => '53 Summit Rd', 'phone' => '0901234570'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
