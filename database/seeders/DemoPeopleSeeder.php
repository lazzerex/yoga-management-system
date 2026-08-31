<?php

namespace Database\Seeders;

use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPeopleSeeder extends Seeder
{
    public function run(): void
    {
        $classTypeIds = ClassType::pluck('id');

        $coaches = ['Maya Chen', 'David Lee', 'Priya Rao', 'Sofia Marin'];

        foreach ($coaches as $i => $name) {
            $user = $this->account($name, 'coach'.($i + 2), 'coach', 'Coach@12345');

            $profile = CoachProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => 'Leads breath-led practice across all levels.',
                    'years_experience' => 3 + $i * 2,
                    'certifications' => 'RYT-200',
                    'is_active' => true,
                ]
            );

            $profile->classTypes()->sync($classTypeIds->shuffle()->take(2));
        }

        $members = [
            'An Tran', 'Bao Nguyen', 'Chi Le', 'Duc Pham', 'Ha Vo', 'Khanh Do',
            'Linh Bui', 'Minh Hoang', 'Nam Dang', 'Oanh Ly', 'Phuc Ngo', 'Quyen Ta',
            'Thao Vu', 'Uyen Cao',
        ];

        foreach ($members as $i => $name) {
            $user = $this->account($name, 'member'.($i + 2), 'member', 'Member@12345');

            StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '09'.str_pad((string) ($i + 2), 8, '0', STR_PAD_LEFT),
                    'goals' => 'Build a consistent weekly practice.',
                    'is_active' => true,
                ]
            );
        }
    }

    private function account(string $name, string $username, string $role, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $username.'@yoga.local'],
            [
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
    }
}
