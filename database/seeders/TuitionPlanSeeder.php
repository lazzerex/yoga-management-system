<?php

namespace Database\Seeders;

use App\Models\TuitionPlan;
use Illuminate\Database\Seeder;

class TuitionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Unlimited Monthly', 'name_vi' => 'Gói tháng không giới hạn', 'type' => 'monthly', 'price_amount' => 1800000, 'session_count' => null, 'duration_days' => 30],
            ['name' => '10-Class Pack', 'name_vi' => 'Gói 10 buổi', 'type' => 'pack', 'price_amount' => 1500000, 'session_count' => 10, 'duration_days' => 90],
            ['name' => '20-Class Pack', 'name_vi' => 'Gói 20 buổi', 'type' => 'pack', 'price_amount' => 2700000, 'session_count' => 20, 'duration_days' => 180],
            ['name' => 'Beginner Course (8 weeks)', 'name_vi' => 'Khoá cơ bản (8 tuần)', 'type' => 'course', 'price_amount' => 2200000, 'session_count' => 16, 'duration_days' => 56],
            ['name' => 'Drop-in Class', 'name_vi' => 'Buổi lẻ', 'type' => 'pack', 'price_amount' => 180000, 'session_count' => 1, 'duration_days' => 7],
            ['name' => '5-Class Starter', 'name_vi' => 'Gói khởi đầu 5 buổi', 'type' => 'pack', 'price_amount' => 850000, 'session_count' => 5, 'duration_days' => 45],
            // Priced as eight months for twelve, which is what an annual pass is worth:
            // at list price it swamped every other slice of the revenue mix.
            ['name' => 'Annual Unlimited', 'name_vi' => 'Gói năm không giới hạn', 'type' => 'monthly', 'price_amount' => 9600000, 'session_count' => null, 'duration_days' => 365],
            ['name' => 'Student Pass', 'name_vi' => 'Gói sinh viên', 'type' => 'monthly', 'price_amount' => 1200000, 'session_count' => null, 'duration_days' => 30],
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
