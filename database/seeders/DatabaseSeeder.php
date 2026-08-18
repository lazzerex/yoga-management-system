<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DummyUsersSeeder::class,
            BranchSeeder::class,
        ]);

        // WithoutModelEvents suppresses the User::booted() saved hook during
        // seeding, so backfill spatie roles explicitly here — see menu.md 10.4.
        User::query()->get()->each(fn (User $user) => $user->syncRoles([$user->role]));
    }
}
