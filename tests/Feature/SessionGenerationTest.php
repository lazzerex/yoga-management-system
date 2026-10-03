<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Modules\Operations\ClassSession\Actions\GenerateClassSessionsAction;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_generates_sessions_for_the_next_eight_weeks(): void
    {
        $schedule = ClassSchedule::factory()->create(['day_of_week' => now()->dayOfWeek]);

        app(GenerateClassSessionsAction::class)->execute();

        $this->assertSame(8, ClassSession::where('class_schedule_id', $schedule->id)->count());
    }

    public function test_backfilled_sessions_are_dated_in_the_past_and_marked_done(): void
    {
        $schedule = ClassSchedule::factory()->create(['day_of_week' => now()->dayOfWeek]);

        app(GenerateClassSessionsAction::class)->execute(weeksBack: 4);

        $past = ClassSession::where('class_schedule_id', $schedule->id)
            ->where('session_date', '<', now()->toDateString())
            ->get();

        $this->assertCount(4, $past);
        $this->assertTrue($past->every(fn (ClassSession $session) => $session->status === 'done'));
        $this->assertSame('scheduled', ClassSession::where('session_date', now()->toDateString())->first()->status);
    }

    public function test_generation_is_idempotent(): void
    {
        ClassSchedule::factory()->create(['day_of_week' => now()->dayOfWeek]);

        app(GenerateClassSessionsAction::class)->execute();
        $firstRunCount = ClassSession::count();

        app(GenerateClassSessionsAction::class)->execute();
        $secondRunCount = ClassSession::count();

        $this->assertSame($firstRunCount, $secondRunCount);
        $this->assertGreaterThan(0, $firstRunCount);
    }

    public function test_generation_skips_a_slot_that_would_conflict_with_an_existing_session(): void
    {
        $branch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);
        $classType = ClassType::factory()->create();
        $coachA = CoachProfile::factory()->create();
        $coachB = CoachProfile::factory()->create();

        $schedule = ClassSchedule::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $room->id,
            'class_type_id' => $classType->id,
            'coach_profile_id' => $coachA->id,
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '18:00',
        ]);

        // Pre-existing session occupying the same room+date+time from a different schedule/coach.
        ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $room->id,
            'coach_profile_id' => $coachB->id,
            'session_date' => now()->toDateString(),
            'start_time' => '18:00',
            'status' => 'scheduled',
        ]);

        app(GenerateClassSessionsAction::class)->execute();

        $this->assertDatabaseMissing('class_sessions', [
            'class_schedule_id' => $schedule->id,
            'session_date' => now()->toDateString(),
        ]);
    }
}
