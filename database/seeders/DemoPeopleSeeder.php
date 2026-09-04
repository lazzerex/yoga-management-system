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

        // One retired coach, so the directory's status filter and the People view's
        // active/inactive split have something to show.
        $coaches = [
            ['name' => 'Maya Chen', 'active' => true],
            ['name' => 'David Lee', 'active' => true],
            ['name' => 'Priya Rao', 'active' => true],
            ['name' => 'Sofia Marin', 'active' => true],
            ['name' => 'Tomas Ferrer', 'active' => false],
        ];

        foreach ($coaches as $i => $coach) {
            $user = $this->account($coach['name'], 'coach'.($i + 2), 'coach', 'Coach@12345');

            $profile = CoachProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => 'Leads breath-led practice across all levels.',
                    'years_experience' => 3 + $i * 2,
                    'certifications' => $i % 2 === 0 ? 'RYT-200' : 'RYT-500, Yin Level 1',
                    'is_active' => $coach['active'],
                ]
            );

            $profile->classTypes()->sync($classTypeIds->shuffle()->take(2));
        }

        $members = [
            'An Tran', 'Bao Nguyen', 'Chi Le', 'Duc Pham', 'Ha Vo', 'Khanh Do',
            'Linh Bui', 'Minh Hoang', 'Nam Dang', 'Oanh Ly', 'Phuc Ngo', 'Quyen Ta',
            'Thao Vu', 'Uyen Cao', 'Vinh Truong', 'Yen Phan', 'Bich Ha', 'Cuong Ly',
            'Dieu Nguyen', 'Giang Vu', 'Hieu Tran', 'Kim Doan', 'Loan Dinh', 'My Trinh',
            'Nga Pham', 'Phong Le', 'Quang Bui', 'Suong Dao', 'Tuan Vo', 'Van Ho',
            'Xuan Mai', 'Anh Kieu', 'Binh Tran', 'Chau Nguyen', 'Dat Luong', 'Em Ngo',
            'Gia Han', 'Hoa Dang', 'Kien Ta', 'Lan Chau', 'Mai Ho', 'Ngoc Vu',
        ];

        $goals = [
            'Build a consistent weekly practice.',
            'Recover mobility after a desk-bound year.',
            'Prepare for a first teacher training.',
            'Manage stress with slower, breath-led classes.',
        ];

        foreach ($members as $i => $name) {
            $user = $this->account($name, 'member'.($i + 2), 'member', 'Member@12345');

            // Joining dates fan out over a year so the student-growth chart has a shape,
            // and every fifth member has lapsed.
            $joinedAt = now()->subMonths(11 - ($i % 12))->startOfMonth()->addDays(($i * 3) % 25);

            $profile = StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'emergency_contact_name' => 'Emergency Contact',
                    'emergency_contact_phone' => '09'.str_pad((string) ($i + 2), 8, '0', STR_PAD_LEFT),
                    'goals' => $goals[$i % count($goals)],
                    'is_active' => $i % 5 !== 4,
                ]
            );

            $profile->forceFill(['created_at' => $joinedAt])->save();
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
