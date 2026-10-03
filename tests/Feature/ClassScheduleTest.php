<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_class_schedule(): void
    {
        $branch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);
        $classType = ClassType::factory()->create();
        $coach = CoachProfile::factory()->create();

        $this->actingAs($this->admin())
            ->post('/cms/operations/class-schedules', [
                'branch_id' => $branch->id,
                'room_id' => $room->id,
                'class_type_id' => $classType->id,
                'coach_profile_id' => $coach->id,
                'day_of_week' => 1,
                'start_time' => '18:00',
                'duration_minutes' => 60,
                'capacity' => 15,
            ])
            ->assertRedirect('/cms/operations/academy');

        $this->assertDatabaseHas('class_schedules', ['room_id' => $room->id, 'day_of_week' => 1]);
    }

    public function test_room_must_belong_to_the_selected_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/class-schedules/create')
            ->post('/cms/operations/class-schedules', $this->payload([
                'branch_id' => $branch->id,
                'room_id' => $room->id,
            ]))
            ->assertSessionHasErrors('room_id');
    }

    public function test_capacity_cannot_exceed_room_capacity(): void
    {
        $branch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 10]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/class-schedules/create')
            ->post('/cms/operations/class-schedules', $this->payload([
                'branch_id' => $branch->id,
                'room_id' => $room->id,
                'capacity' => 25,
            ]))
            ->assertSessionHasErrors('capacity');
    }

    public function test_two_schedules_cannot_share_the_same_room_day_and_time(): void
    {
        $branch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);
        ClassSchedule::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $room->id,
            'day_of_week' => 2,
            'start_time' => '18:00',
        ]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/class-schedules/create')
            ->post('/cms/operations/class-schedules', $this->payload([
                'branch_id' => $branch->id,
                'room_id' => $room->id,
                'class_type_id' => ClassType::factory()->create()->id,
                'coach_profile_id' => CoachProfile::factory()->create()->id,
                'day_of_week' => 2,
                'start_time' => '18:00',
            ]))
            ->assertSessionHasErrors('room_id');
    }

    public function test_two_schedules_cannot_share_the_same_coach_day_and_time(): void
    {
        $coach = CoachProfile::factory()->create();
        ClassSchedule::factory()->create([
            'coach_profile_id' => $coach->id,
            'day_of_week' => 4,
            'start_time' => '07:00',
        ]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/class-schedules/create')
            ->post('/cms/operations/class-schedules', $this->payload([
                'coach_profile_id' => $coach->id,
                'day_of_week' => 4,
                'start_time' => '07:00',
            ]))
            ->assertSessionHasErrors('room_id');
    }

    public function test_admin_can_delete_a_class_schedule_without_sessions(): void
    {
        $schedule = ClassSchedule::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/class-schedules/{$schedule->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('class_schedules', ['id' => $schedule->id]);
    }

    public function test_class_schedule_with_generated_sessions_cannot_be_deleted(): void
    {
        $schedule = ClassSchedule::factory()->create();
        ClassSession::factory()->create(['class_schedule_id' => $schedule->id]);

        $this->actingAs($this->admin())
            ->delete("/cms/operations/class-schedules/{$schedule->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('class_schedules', ['id' => $schedule->id]);
    }

    public function test_coach_cannot_manage_class_schedules(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $this->actingAs($coach)->get('/cms/operations/academy')->assertOk();
        $this->actingAs($coach)->get('/cms/operations/class-schedules/create')->assertForbidden();
    }

    public function test_admin_can_generate_sessions_from_the_academy_page(): void
    {
        ClassSchedule::factory()->create(['day_of_week' => now()->dayOfWeek]);

        $this->actingAs($this->admin())
            ->post('/cms/operations/class-schedules/generate-sessions')
            ->assertRedirect('/cms/operations/academy');

        $this->assertGreaterThan(0, ClassSession::count());
    }

    public function test_generating_sessions_again_does_not_duplicate(): void
    {
        ClassSchedule::factory()->create(['day_of_week' => now()->dayOfWeek]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/cms/operations/class-schedules/generate-sessions');
        $firstRunCount = ClassSession::count();

        $this->actingAs($admin)->post('/cms/operations/class-schedules/generate-sessions');
        $secondRunCount = ClassSession::count();

        $this->assertSame($firstRunCount, $secondRunCount);
    }

    public function test_coach_cannot_generate_sessions(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $this->actingAs($coach)
            ->post('/cms/operations/class-schedules/generate-sessions')
            ->assertForbidden();
    }

    public function test_generate_sessions_button_is_hidden_without_the_permission(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $page = $this->actingAs($coach)->get('/cms/operations/academy')->viewData('page');

        $this->assertNull($page['props']['endpoints']['generateSessions']);
    }

    public function test_academy_page_scopes_schedules_and_sessions_to_the_current_branch_cookie(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $scheduleA = ClassSchedule::factory()->create(['branch_id' => $branchA->id]);
        $scheduleB = ClassSchedule::factory()->create(['branch_id' => $branchB->id]);
        ClassSession::factory()->create(['class_schedule_id' => $scheduleA->id, 'branch_id' => $branchA->id]);
        ClassSession::factory()->create(['class_schedule_id' => $scheduleB->id, 'branch_id' => $branchB->id]);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $branchA->id)
            ->get('/cms/operations/academy')
            ->viewData('page');

        $this->assertCount(1, $page['props']['schedules']['data']);
        $this->assertCount(1, $page['props']['sessions']['data']);
        $this->assertSame($branchA->id, $page['props']['currentBranch']['id']);
    }

    public function test_class_schedule_create_page_prefills_branch_from_current_branch_cookie(): void
    {
        $branch = Branch::factory()->create();

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $branch->id)
            ->get('/cms/operations/class-schedules/create')
            ->viewData('page');

        $this->assertSame($branch->id, $page['props']['selectedBranchId']);
    }

    private function payload(array $overrides = []): array
    {
        $branch = Branch::factory()->create();
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 20]);

        return array_merge([
            'branch_id' => $branch->id,
            'room_id' => $room->id,
            'class_type_id' => ClassType::factory()->create()->id,
            'coach_profile_id' => CoachProfile::factory()->create()->id,
            'day_of_week' => 1,
            'start_time' => '18:00',
            'duration_minutes' => 60,
            'capacity' => 15,
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
