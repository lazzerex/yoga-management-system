<?php

namespace Database\Seeders;

use App\Models\TuitionPlan;
use Illuminate\Database\Seeder;

class TuitionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Unlimited Monthly', 'type' => 'monthly', 'price_amount' => 1800000, 'session_count' => null, 'duration_days' => 30],
            ['name' => '10-Class Pack', 'type' => 'pack', 'price_amount' => 1500000, 'session_count' => 10, 'duration_days' => 90],
            ['name' => '20-Class Pack', 'type' => 'pack', 'price_amount' => 2700000, 'session_count' => 20, 'duration_days' => 180],
            ['name' => 'Beginner Course (8 weeks)', 'type' => 'course', 'price_amount' => 2200000, 'session_count' => 16, 'duration_days' => 56],
        ];

        foreach ($plans as $plan) {
            TuitionPlan::create($plan + [
                'branch_id' => null,
                'description' => null,
                'is_active' => true,
            ]);
        }
    }
}
