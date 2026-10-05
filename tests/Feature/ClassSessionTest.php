<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
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

    public function test_session_detail_shows_the_roster_to_an_admin(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        $booked = Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);
        Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id]);

        $this->actingAs($this->admin())
            ->getJson("/cms/operations/class-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('booked_count', 1)
            ->assertJsonPath('waitlist_count', 1)
            ->assertJsonPath('roster.0.reference', $booked->reference())
            ->assertJsonPath('endpoints.cancel', route('operations.class-sessions.cancel', $session));
    }

    public function test_session_detail_hides_the_roster_from_a_member_and_another_coach(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);

        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member)->getJson("/cms/operations/class-sessions/{$session->id}")
            ->assertOk()
            ->assertJsonPath('roster', null)
            ->assertJsonPath('endpoints.cancel', null);

        $otherCoach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $otherCoach->id]);
        $this->actingAs($otherCoach)->getJson("/cms/operations/class-sessions/{$session->id}")
            ->assertJsonPath('roster', null);

        $this->actingAs($session->coachProfile->user)->getJson("/cms/operations/class-sessions/{$session->id}")
            ->assertJsonCount(1, 'roster');
    }

    public function test_cancelling_a_session_returns_the_plan_session_it_used(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        $student = StudentProfile::factory()->create();
        $invoice = Invoice::factory()->create(['student_profile_id' => $student->id, 'status' => 'paid']);
        $line = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'tuition_plan_id' => TuitionPlan::factory()->create()->id,
            'description' => 'Pack',
            'quantity' => 1,
            'unit_price' => 0,
            'line_total' => 0,
            'valid_until' => now()->addMonth()->toDateString(),
            'sessions_granted' => 5,
        ]);
        Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $student->id,
            'invoice_item_id' => $line->id,
            'status' => 'booked',
        ]);
        $this->assertSame(4, $line->fresh()->sessionsRemaining());

        $this->actingAs($this->admin())
            ->post("/cms/operations/class-sessions/{$session->id}/cancel")
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertSame(5, $line->fresh()->sessionsRemaining());
    }

    public function test_a_past_session_cannot_be_cancelled_and_a_coach_cannot_cancel(): void
    {
        $past = ClassSession::factory()->create(['session_date' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin())
            ->post("/cms/operations/class-sessions/{$past->id}/cancel")
            ->assertSessionHasErrors('action');
        $this->assertSame('scheduled', $past->fresh()->status);

        $upcoming = ClassSession::factory()->create(['session_date' => now()->addDay()->toDateString()]);
        $this->actingAs(User::factory()->create(['role' => 'coach']))
            ->post("/cms/operations/class-sessions/{$upcoming->id}/cancel")
            ->assertForbidden();
    }

    public function test_academy_calendar_lists_the_sessions_of_the_requested_week(): void
    {
        $monday = now()->startOfWeek()->addWeek();
        $inWeek = ClassSession::factory()->create(['session_date' => $monday->copy()->addDays(2)->toDateString()]);
        ClassSession::factory()->create([
            'session_date' => $monday->copy()->addDays(8)->toDateString(),
            'branch_id' => $inWeek->branch_id,
        ]);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $inWeek->branch_id)
            ->get('/cms/operations/academy?week='.$monday->toDateString())
            ->viewData('page');

        $this->assertSame($monday->toDateString(), $page['props']['week']);
        $this->assertSame([$inWeek->id], collect($page['props']['calendar'])->pluck('id')->all());
    }

    public function test_academy_finds_sessions_by_part_of_their_code(): void
    {
        $wanted = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        ClassSession::factory()->count(2)->create(['session_date' => now()->addDays(2)->toDateString(), 'branch_id' => $wanted->branch_id]);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $wanted->branch_id)
            ->get('/cms/operations/academy?search='.substr($wanted->reference(), -2))
            ->viewData('page');

        $this->assertContains($wanted->reference(), collect($page['props']['sessions']['data'])->pluck('reference')->all());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
