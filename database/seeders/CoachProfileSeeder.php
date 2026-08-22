<?php

namespace Database\Seeders;

use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoachProfileSeeder extends Seeder
{
    public function run(): void
    {
        $coach = User::where('email', 'coach@yoga.local')->first();

        if (! $coach) {
            return;
        }

        $profile = CoachProfile::updateOrCreate(
            ['user_id' => $coach->id],
            [
                'bio' => 'Certified yoga instructor with a focus on breath-led practice.',
                'years_experience' => 5,
                'certifications' => 'RYT-200',
            ]
        );

        $profile->classTypes()->sync(ClassType::pluck('id')->take(2));
    }
}
