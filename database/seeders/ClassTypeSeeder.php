<?php

namespace Database\Seeders;

use App\Models\ClassType;
use Illuminate\Database\Seeder;

class ClassTypeSeeder extends Seeder
{
    public function run(): void
    {
        $classTypes = [
            ['name' => 'Hatha Yoga', 'description' => 'Slow-paced, foundational postures.'],
            ['name' => 'Vinyasa Flow', 'description' => 'Breath-synchronized, dynamic movement.'],
            ['name' => 'Yin Yoga', 'description' => 'Passive, long-held stretches for deep tissue.'],
            ['name' => 'Prenatal Yoga', 'description' => 'Adapted postures for pregnancy.'],
        ];

        foreach ($classTypes as $classType) {
            ClassType::updateOrCreate(['name' => $classType['name']], $classType);
        }
    }
}
