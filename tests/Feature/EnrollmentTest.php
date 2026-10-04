<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_member_can_enroll_in_a_session_under_capacity(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 10,
        ]);
        [$user, $studentProfile] = $this->member();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_profile_id' => $studentProfile->id,
            'class_session_id' => $session->id,
            'status' => 'booked',
        ]);
    }

    public function test_member_is_waitlisted_when_session_is_at_capacity(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 1,
        ]);
        Enrollment::factory()->create(['class_session_id' => $session->id]);
        [$user, $studentProfile] = $this->member();

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");

        $this->assertDatabaseHas('enrollments', [
            'student_profile_id' => $studentProfile->id,
            'class_session_id' => $session->id,
            'status' => 'waitlisted',
        ]);
    }

    public function test_member_cannot_enroll_twice_in_the_same_session(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $this->actingAs($user)
            ->from('/cms/member/my-classes')
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors('action');
    }

    public function test_member_cannot_enroll_in_a_cancelled_session(): void
    {
        $session = ClassSession::factory()->cancelled()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user] = $this->member();

        $this->actingAs($user)
            ->from('/cms/member/my-classes')
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors('action');
    }

    public function test_member_can_cancel_own_enrollment_before_the_cutoff(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        $enrollment = Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $this->actingAs($user)
            ->delete("/cms/member/enrollments/{$enrollment->id}")
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'cancelled']);
    }

    public function test_member_cannot_cancel_within_the_cutoff_window(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
        ]);
        [$user, $studentProfile] = $this->member();
        $enrollment = Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $this->actingAs($user)
            ->from('/cms/member/my-classes')
            ->delete("/cms/member/enrollments/{$enrollment->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'booked']);
    }

    public function test_cancelling_a_booked_enrollment_promotes_the_oldest_waitlisted_one(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 1,
        ]);
        $booked = Enrollment::factory()->create(['class_session_id' => $session->id, 'enrolled_at' => now()->subMinutes(10)]);
        $waitlistedFirst = Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id, 'enrolled_at' => now()->subMinutes(5)]);
        $waitlistedSecond = Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id, 'enrolled_at' => now()]);

        $studentProfile = $booked->studentProfile;
        $user = $studentProfile->user;

        $this->actingAs($user)->delete("/cms/member/enrollments/{$booked->id}");

        $this->assertSame('booked', $waitlistedFirst->fresh()->status);
        $this->assertSame('waitlisted', $waitlistedSecond->fresh()->status);
    }

    public function test_member_cannot_cancel_another_members_enrollment(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        $owner = Enrollment::factory()->create(['class_session_id' => $session->id]);
        [$otherUser] = $this->member();

        $this->actingAs($otherUser)
            ->delete("/cms/member/enrollments/{$owner->id}")
            ->assertForbidden();

        $this->assertSame('booked', $owner->fresh()->status);
    }

    public function test_admin_can_cancel_any_enrollment(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($this->admin())
            ->delete("/cms/operations/enrollments/{$enrollment->id}")
            ->assertRedirect();

        $this->assertSame('cancelled', $enrollment->fresh()->status);
    }

    public function test_admin_can_cancel_inside_the_member_cutoff_window(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->toDateString(),
            'start_time' => now()->addMinutes(30)->format('H:i:s'),
        ]);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($this->admin())
            ->delete("/cms/operations/enrollments/{$enrollment->id}")
            ->assertRedirect();

        $this->assertSame('cancelled', $enrollment->fresh()->status);
    }

    public function test_admin_cancellation_is_written_to_the_audit_log(): void
    {
        $admin = $this->admin();
        [$memberUser, $studentProfile] = $this->member();
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $studentProfile->id,
        ]);

        $this->actingAs($admin)->delete("/cms/operations/enrollments/{$enrollment->id}");

        $log = AuditLog::where('action', 'cancel_enrollment')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame($memberUser->name, $log->subject_name);
        $this->assertSame('booked', $log->meta['from']);
        $this->assertSame($enrollment->id, $log->meta['enrollment_id']);
    }

    public function test_member_cancelling_their_own_booking_is_not_audited(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $studentProfile->id,
        ]);

        $this->actingAs($user)->delete("/cms/member/enrollments/{$enrollment->id}");

        $this->assertSame('cancelled', $enrollment->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_updating_a_template_does_not_overwrite_a_session_with_an_active_enrollment(): void
    {
        $room = Room::factory()->create(['capacity' => 25]);
        $schedule = ClassSchedule::factory()->create(['room_id' => $room->id, 'branch_id' => $room->branch_id, 'capacity' => 15]);
        $bookedSession = ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $schedule->class_type_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => $schedule->start_time,
            'capacity' => 15,
            'status' => 'scheduled',
            'is_overridden' => false,
        ]);
        Enrollment::factory()->create(['class_session_id' => $bookedSession->id]);

        $freeSession = ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $schedule->class_type_id,
            'coach_profile_id' => $schedule->coach_profile_id,
            'session_date' => now()->addDays(4)->toDateString(),
            'start_time' => $schedule->start_time,
            'capacity' => 15,
            'status' => 'scheduled',
            'is_overridden' => false,
        ]);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/class-schedules/{$schedule->id}", [
                'branch_id' => $schedule->branch_id,
                'room_id' => $schedule->room_id,
                'class_type_id' => $schedule->class_type_id,
                'coach_profile_id' => $schedule->coach_profile_id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr($schedule->start_time, 0, 5),
                'duration_minutes' => $schedule->duration_minutes,
                'capacity' => 20,
            ])
            ->assertRedirect();

        $this->assertSame(15, $bookedSession->fresh()->capacity);
        $this->assertSame(20, $freeSession->fresh()->capacity);
    }

    public function test_my_classes_page_lists_own_enrollments(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $page = $this->actingAs($user)->get('/cms/member/my-classes')->viewData('page');

        $this->assertSame('Member/MyClasses', $page['component']);
        $this->assertCount(1, $page['props']['myEnrollments']);
        $this->assertSame($session->id, $page['props']['myEnrollments'][0]['id']);
    }

    public function test_my_classes_enrollment_payload_carries_full_booking_detail(): void
    {
        $branch = Branch::factory()->create(['address' => '10 Lotus Lane']);
        $room = Room::factory()->create(['branch_id' => $branch->id, 'capacity' => 18]);
        $classType = ClassType::factory()->create(['description' => 'Slow, breath-led practice.']);
        $coachProfile = CoachProfile::factory()->create(['bio' => 'Ten years teaching Hatha.', 'years_experience' => 10]);
        $session = ClassSession::factory()->create([
            'branch_id' => $branch->id,
            'room_id' => $room->id,
            'class_type_id' => $classType->id,
            'coach_profile_id' => $coachProfile->id,
            'session_date' => now()->addDays(5)->toDateString(),
            'capacity' => 12,
        ]);
        Enrollment::factory()->count(3)->create(['class_session_id' => $session->id, 'status' => 'booked']);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $row = $this->actingAs($user)->get('/cms/member/my-classes')->viewData('page')['props']['myEnrollments'][0];

        $this->assertSame('Slow, breath-led practice.', $row['class_type_description']);
        $this->assertSame('Ten years teaching Hatha.', $row['coach_bio']);
        $this->assertSame(10, $row['coach_years_experience']);
        $this->assertSame('10 Lotus Lane', $row['branch_address']);
        $this->assertSame(18, $row['room_capacity']);
        $this->assertSame(12, $row['capacity']);
        $this->assertSame(4, $row['booked_count']);
        $this->assertSame(8, $row['spots_left']);
        $this->assertNotNull($row['enrolled_at']);
        $this->assertNotNull($row['cancel_deadline']);
    }

    public function test_book_page_lists_available_sessions_excluding_already_enrolled(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        $otherSession = ClassSession::factory()->create([
            'session_date' => now()->addDays(4)->toDateString(),
            'branch_id' => $session->branch_id,
        ]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $page = $this->actingAs($user)
            ->withUnencryptedCookie('branch_id', $session->branch_id)
            ->get('/cms/member/book')->viewData('page');

        $this->assertSame('Member/BookClass', $page['component']);
        $availableIds = collect($page['props']['availableSessions'])->pluck('id')->all();
        $this->assertNotContains($session->id, $availableIds);
        $this->assertContains($otherSession->id, $availableIds);
    }

    public function test_available_sessions_expose_capacity_and_waitlist_counts(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 4,
        ]);
        Enrollment::factory()->count(4)->create(['class_session_id' => $session->id, 'status' => 'booked']);
        Enrollment::factory()->count(2)->waitlisted()->create(['class_session_id' => $session->id]);
        [$user] = $this->member();

        $page = $this->actingAs($user)
            ->withUnencryptedCookie('branch_id', $session->branch_id)
            ->get('/cms/member/book')->viewData('page');
        $row = collect($page['props']['availableSessions'])->firstWhere('id', $session->id);

        $this->assertSame(4, $row['capacity']);
        $this->assertSame(4, $row['booked_count']);
        $this->assertSame(0, $row['spots_left']);
        $this->assertSame(2, $row['waitlist_count']);
    }

    public function test_waitlisted_enrollment_exposes_its_queue_position(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 1,
        ]);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);
        Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id, 'enrolled_at' => now()->subMinutes(10)]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->waitlisted()->create([
            'student_profile_id' => $studentProfile->id,
            'class_session_id' => $session->id,
            'enrolled_at' => now(),
        ]);

        $page = $this->actingAs($user)->get('/cms/member/my-classes')->viewData('page');

        $this->assertSame(2, $page['props']['myEnrollments'][0]['waitlist_position']);
    }

    public function test_my_classes_flags_an_enrollment_inside_the_cutoff_as_not_cancellable(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
        ]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $session->id]);

        $page = $this->actingAs($user)->get('/cms/member/my-classes')->viewData('page');

        $this->assertFalse($page['props']['myEnrollments'][0]['can_cancel']);
    }

    public function test_booking_a_full_session_flashes_the_waitlist_message(): void
    {
        $session = ClassSession::factory()->create([
            'session_date' => now()->addDays(3)->toDateString(),
            'capacity' => 1,
        ]);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);
        [$user] = $this->member();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHas('success', ['key' => 'flash.enrollmentWaitlisted']);
    }

    public function test_member_can_rebook_a_session_they_previously_cancelled(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->cancelled()->create([
            'student_profile_id' => $studentProfile->id,
            'class_session_id' => $session->id,
        ]);

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertRedirect();

        $this->assertSame(1, Enrollment::where('student_profile_id', $studentProfile->id)
            ->where('class_session_id', $session->id)
            ->where('status', 'booked')
            ->count());
    }

    public function test_admin_can_view_the_enrollments_oversight_page(): void
    {
        $session = ClassSession::factory()->create(['session_date' => now()->addDays(3)->toDateString()]);
        Enrollment::factory()->create(['class_session_id' => $session->id]);

        $page = $this->actingAs($this->admin())->get('/cms/operations/enrollments')->viewData('page');

        $this->assertSame('Operations/Enrollments/Index', $page['component']);
        $this->assertCount(1, $page['props']['enrollments']['data']);
    }

    public function test_members_cannot_view_the_enrollments_oversight_page(): void
    {
        [$user] = $this->member();

        $this->actingAs($user)->get('/cms/operations/enrollments')->assertForbidden();
    }

    public function test_member_my_schedule_page_shows_only_booked_sessions_within_the_next_week(): void
    {
        $bookedSession = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        $waitlistedSession = ClassSession::factory()->create(['session_date' => now()->addDays(2)->toDateString()]);
        $farSession = ClassSession::factory()->create(['session_date' => now()->addDays(10)->toDateString()]);
        [$user, $studentProfile] = $this->member();
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $bookedSession->id, 'status' => 'booked']);
        Enrollment::factory()->waitlisted()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $waitlistedSession->id]);
        Enrollment::factory()->create(['student_profile_id' => $studentProfile->id, 'class_session_id' => $farSession->id, 'status' => 'booked']);

        $page = $this->actingAs($user)->get('/cms/member/my-schedule')->viewData('page');

        $dates = collect($page['props']['sessions'])->pluck('session_date')->all();
        $this->assertContains($bookedSession->session_date, $dates);
        $this->assertCount(1, $dates);
    }

    public function test_coach_my_classes_page_shows_roster_counts_from_the_next_session(): void
    {
        $coachUser = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coachUser->id]);
        $room = Room::factory()->create(['capacity' => 25]);
        $classType = ClassType::factory()->create();
        $schedule = ClassSchedule::factory()->create([
            'coach_profile_id' => $coachProfile->id,
            'room_id' => $room->id,
            'branch_id' => $room->branch_id,
            'class_type_id' => $classType->id,
            'capacity' => 10,
        ]);
        $nextSession = ClassSession::factory()->create([
            'class_schedule_id' => $schedule->id,
            'coach_profile_id' => $coachProfile->id,
            'branch_id' => $schedule->branch_id,
            'room_id' => $schedule->room_id,
            'class_type_id' => $schedule->class_type_id,
            'session_date' => now()->addDays(2)->toDateString(),
            'capacity' => 10,
            'status' => 'scheduled',
        ]);
        Enrollment::factory()->count(2)->create(['class_session_id' => $nextSession->id, 'status' => 'booked']);
        Enrollment::factory()->waitlisted()->create(['class_session_id' => $nextSession->id]);

        $page = $this->actingAs($coachUser)->get('/cms/coach/my-classes')->viewData('page');

        $this->assertSame(2, $page['props']['classes'][0]['students']);
        $this->assertSame(1, $page['props']['classes'][0]['waitlist']);
        $this->assertSame(2, $page['props']['stats']['totalStudents']);
    }

    public function test_coach_teaching_schedule_shows_booked_student_count(): void
    {
        $coachUser = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::factory()->create(['user_id' => $coachUser->id]);
        $session = ClassSession::factory()->create([
            'coach_profile_id' => $coachProfile->id,
            'session_date' => now()->addDays(1)->toDateString(),
            'status' => 'scheduled',
        ]);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);
        Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id]);

        $page = $this->actingAs($coachUser)->get('/cms/coach/my-teaching-schedule')->viewData('page');

        $this->assertSame(1, $page['props']['sessions'][0]['students']);
    }

    /** A member in good standing, so these tests stay about capacity, waitlist and cutoff. */
    private function member(): array
    {
        $user = User::factory()->create(['role' => 'member']);
        $studentProfile = StudentProfile::factory()->create(['user_id' => $user->id]);

        Invoice::factory()
            ->create(['student_profile_id' => $studentProfile->id, 'status' => 'paid'])
            ->items()
            ->create([
                'tuition_plan_id' => TuitionPlan::factory()->create(['session_count' => null])->id,
                'description' => 'Unlimited Monthly',
                'quantity' => 1,
                'unit_price' => 0,
                'line_total' => 0,
                'valid_from' => today()->subDay()->toDateString(),
                'valid_until' => today()->addYear()->toDateString(),
                'sessions_granted' => null,
            ]);

        return [$user, $studentProfile];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
