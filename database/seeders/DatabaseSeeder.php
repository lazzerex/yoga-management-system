<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Operations\ClassSession\Actions\GenerateClassSessionsAction;
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
            RoomSeeder::class,
            ClassTypeSeeder::class,
            CoachProfileSeeder::class,
            StudentProfileSeeder::class,
            DemoPeopleSeeder::class,
            ClassScheduleSeeder::class,
        ]);

        // Twenty weeks back: enough history for the twelve-week trend charts, without
        // seeding a year of enrolments nobody reads.
        app(GenerateClassSessionsAction::class)->execute(weeksBack: 20);

        $this->call([
            EnrollmentSeeder::class,
            SessionVarietySeeder::class,
            AttendanceSeeder::class,
            LessonPlanSeeder::class,
            TuitionPlanSeeder::class,
            InvoiceSeeder::class,
            MediaSeeder::class,
        ]);

        // WithoutModelEvents suppresses the User::booted() saved hook during
        // seeding, so backfill spatie roles explicitly here — see menu.md 10.4.
        User::query()->get()->each(fn (User $user) => $user->syncRoles([$user->role]));
    }
}
