<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Models\User;
use App\Modules\Operations\ClassSchedule\Actions\UpdateClassScheduleAction;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSessionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_reassign_room_and_coach_for_a_single_session(): void
    {
        $session = ClassSession::factory()->create();
        $newRoom = Room::factory()->create(['branch_id' => $session->branch_id, 'capacity' => 20]);
        $newCoach = CoachProfile::factory()->create();

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-sessions/{$session->id}", [
                'room_id' => $newRoom->id,
                'coach_profile_id' => $newCoach->id,
                'capacity' => 12,
                'status' => 'scheduled',
            ])
            ->assertRedirect('/cms/operations/academy');

        $session->refresh();
        $this->assertSame($newRoom->id, $session->room_id);
        $this->assertSame($newCoach->id, $session->coach_profile_id);
        $this->assertTrue($session->is_overridden);
    }

    public function test_cancelling_a_session_marks_it_overridden(): void
    {
        $session = ClassSession::factory()->create();

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-sessions/{$session->id}", [
                'room_id' => $session->room_id,
                'coach_profile_id' => $session->coach_profile_id,
                'capacity' => $session->capacity,
                'status' => 'cancelled',
            ])
            ->assertRedirect('/cms/operations/academy');

        $session->refresh();
        $this->assertSame('cancelled', $session->status);
        $this->assertTrue($session->is_overridden);
    }

    public function test_reassigning_a_session_into_a_room_conflict_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $roomA = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);
        $roomB = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);

        $blocker = ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomB->id,
            'session_date' => '2026-09-07',
            'start_time' => '18:00:00',
        ]);
        $target = ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $roomA->id,
            'session_date' => '2026-09-07',
            'start_time' => '18:00:00',
        ]);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-sessions/{$target->id}", [
                'room_id' => $roomB->id,
                'coach_profile_id' => $target->coach_profile_id,
                'capacity' => $target->capacity,
                'status' => 'scheduled',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame($roomA->id, $target->fresh()->room_id);
    }

    public function test_updating_a_schedule_cascades_to_future_unoverridden_sessions(): void
    {
        $schedule = ClassSchedule::factory()->create(['start_time' => '18:00', 'duration_minutes' => 60]);
        $newCoach = CoachProfile::factory()->create();
        $newClassType = ClassType::factory()->create();

        $future = ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => now()->addWeek()->toDateString(),
            'is_overridden' => false,
            'status' => 'scheduled',
        ]);
        $overridden = ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => now()->addWeeks(2)->toDateString(),
            'is_overridden' => true,
            'status' => 'scheduled',
        ]);

        app(UpdateClassScheduleAction::class)->execute($schedule, [
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $newClassType->id,
            'coach_profile_id' => $newCoach->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => '18:00',
            'duration_minutes' => 60,
            'capacity' => $schedule->capacity,
            'is_active' => true,
        ]);

        $this->assertSame($newCoach->id, $future->fresh()->coach_profile_id);
        $this->assertNotSame($newCoach->id, $overridden->fresh()->coach_profile_id);
    }

    public function test_coach_cannot_edit_sessions(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $session = ClassSession::factory()->create();

        $this->actingAs($coach)->get("/cms/operations/class-sessions/{$session->id}/edit")->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
