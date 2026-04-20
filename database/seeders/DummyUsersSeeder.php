<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUsersSeeder extends Seeder
{
    /**
     * Seed the application's dummy users.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@yoga.local',
                'password' => 'Admin@12345',
                'role' => 'admin',
            ],
            [
                'name' => 'Coach User',
                'username' => 'coach',
                'email' => 'coach@yoga.local',
                'password' => 'Coach@12345',
                'role' => 'coach',
            ],
            [
                'name' => 'Member User',
                'username' => 'member',
                'email' => 'member@yoga.local',
                'password' => 'Member@12345',
                'role' => 'member',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'password' => Hash::make($user['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
