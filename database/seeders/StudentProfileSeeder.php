<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $member = User::where('email', 'member@yoga.local')->first();

        if (! $member) {
            return;
        }

        StudentProfile::updateOrCreate(
            ['user_id' => $member->id],
            [
                'emergency_contact_name' => 'Jamie Nguyen',
                'emergency_contact_phone' => '0909876543',
                'goals' => 'Improve flexibility and reduce stress.',
            ]
        );
    }
}
