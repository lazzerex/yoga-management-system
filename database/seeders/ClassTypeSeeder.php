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
            ['name' => 'Ashtanga', 'description' => 'Set sequence, strong and structured.'],
            ['name' => 'Power Flow', 'description' => 'Strength-led vinyasa at pace.'],
            ['name' => 'Restorative', 'description' => 'Supported postures held for deep rest.'],
            ['name' => 'Hot Yoga', 'description' => 'Practised in a heated room.'],
            ['name' => 'Kids Yoga', 'description' => 'Playful, short sequences for children.'],
            ['name' => 'Meditation & Breathwork', 'description' => 'Seated practice, no asana.'],
        ];

        foreach ($classTypes as $classType) {
            ClassType::updateOrCreate(['name' => $classType['name']], $classType);
        }
    }
}
