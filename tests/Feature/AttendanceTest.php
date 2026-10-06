<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\TeacherAttendance;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_coach_can_check_in_to_their_own_session(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);

        $this->actingAs($user)
            ->post("/cms/operations/attendance/{$session->id}/check-in")
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_attendances', [
            'class_session_id' => $session->id,
            'coach_profile_id' => $coachProfile->id,
        ]);
    }

    public function test_coach_cannot_check_in_to_another_coaches_session(): void
    {
        [$user] = $this->coach();
        $session = ClassSession::factory()->create(['session_date' => now()->toDateString()]);

        $this->actingAs($user)
            ->post("/cms/operations/attendance/{$session->id}/check-in")
            ->assertForbidden();

        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_checking_in_twice_is_rejected(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);

        $this->actingAs($user)->post("/cms/operations/attendance/{$session->id}/check-in");

        $this->actingAs($user)
            ->from('/cms/operations/teacher-attendance')
            ->post("/cms/operations/attendance/{$session->id}/check-in")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseCount('teacher_attendances', 1);
    }

    public function test_check_out_records_the_taught_shift(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        TeacherAttendance::create([
            'class_session_id' => $session->id,
            'coach_profile_id' => $coachProfile->id,
            'checked_in_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->post("/cms/operations/attendance/{$session->id}/check-out")
            ->assertRedirect();

        $attendance = TeacherAttendance::first();
        $this->assertNotNull($attendance->checked_out_at);
        $this->assertSame(60, $attendance->taughtMinutes());
    }

    public function test_check_out_without_check_in_is_rejected(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);

        $this->actingAs($user)
            ->from('/cms/operations/teacher-attendance')
            ->post("/cms/operations/attendance/{$session->id}/check-out")
            ->assertSessionHasErrors('action');
    }

    public function test_check_in_is_rejected_for_a_cancelled_session(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile, ['status' => 'cancelled']);

        $this->actingAs($user)
            ->from('/cms/operations/teacher-attendance')
            ->post("/cms/operations/attendance/{$session->id}/check-in")
            ->assertSessionHasErrors('action');
    }

    public function test_coach_can_mark_the_roster_of_their_own_session(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)
            ->post("/cms/operations/attendance/{$session->id}/mark", [
                'entries' => [
                    ['enrollment_id' => $enrollment->id, 'status' => 'late', 'notes' => 'Arrived 10 minutes in'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_attendances', [
            'enrollment_id' => $enrollment->id,
            'status' => 'late',
            'notes' => 'Arrived 10 minutes in',
            'marked_by_user_id' => $user->id,
        ]);
    }

    public function test_marking_the_roster_again_updates_instead_of_duplicating(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        foreach (['absent', 'present'] as $status) {
            $this->actingAs($user)->post("/cms/operations/attendance/{$session->id}/mark", [
                'entries' => [['enrollment_id' => $enrollment->id, 'status' => $status]],
            ]);
        }

        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('student_attendances', [
            'enrollment_id' => $enrollment->id,
            'status' => 'present',
        ]);
    }

    public function test_marking_ignores_enrollments_from_another_session(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        $foreign = Enrollment::factory()->create();

        $this->actingAs($user)->post("/cms/operations/attendance/{$session->id}/mark", [
            'entries' => [['enrollment_id' => $foreign->id, 'status' => 'present']],
        ]);

        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_marking_rejects_an_unknown_status(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)
            ->from("/cms/operations/attendance/{$session->id}")
            ->post("/cms/operations/attendance/{$session->id}/mark", [
                'entries' => [['enrollment_id' => $enrollment->id, 'status' => 'holiday']],
            ])
            ->assertSessionHasErrors('entries.0.status');
    }

    public function test_coach_cannot_mark_another_coaches_roster(): void
    {
        [$user] = $this->coach();
        $session = ClassSession::factory()->create(['session_date' => now()->toDateString()]);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)
            ->post("/cms/operations/attendance/{$session->id}/mark", [
                'entries' => [['enrollment_id' => $enrollment->id, 'status' => 'present']],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_mark_any_roster(): void
    {
        $admin = $this->admin();
        $session = ClassSession::factory()->create(['session_date' => now()->toDateString()]);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($admin)
            ->post("/cms/operations/attendance/{$session->id}/mark", [
                'entries' => [['enrollment_id' => $enrollment->id, 'status' => 'present']],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_attendances', [
            'enrollment_id' => $enrollment->id,
            'marked_by_user_id' => $admin->id,
        ]);
    }

    public function test_members_cannot_reach_the_attendance_board(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/cms/operations/teacher-attendance')->assertForbidden();
    }

    public function test_board_lists_only_the_coaches_own_sessions(): void
    {
        [$user, $coachProfile] = $this->coach();
        $own = $this->sessionFor($coachProfile);
        ClassSession::factory()->create(['session_date' => now()->toDateString(), 'branch_id' => $own->branch_id]);

        $response = $this->actingAs($user)
            ->withUnencryptedCookie('branch_id', (string) $own->branch_id)
            ->get('/cms/operations/teacher-attendance');
        $sessions = $response->getOriginalContent()->getData()['page']['props']['sessions'];

        $this->assertCount(1, $sessions);
        $this->assertSame($own->id, $sessions[0]['id']);
    }

    public function test_board_reports_roster_progress_for_the_selected_day(): void
    {
        $admin = $this->admin();
        $date = now()->addDay()->toDateString();
        $session = ClassSession::factory()->create(['session_date' => $date, 'capacity' => 5]);
        $enrollments = Enrollment::factory()->count(2)->create(['class_session_id' => $session->id]);
        StudentAttendance::factory()->create(['enrollment_id' => $enrollments->first()->id]);

        $response = $this->actingAs($admin)->get("/cms/operations/teacher-attendance?date={$date}");
        $row = $response->getOriginalContent()->getData()['page']['props']['sessions'][0];

        $this->assertSame(2, $row['booked_count']);
        $this->assertSame(1, $row['marked_count']);
    }

    public function test_roster_page_lists_booked_students_with_their_marks(): void
    {
        [$user, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile);
        $booked = Enrollment::factory()->create(['class_session_id' => $session->id]);
        Enrollment::factory()->waitlisted()->create(['class_session_id' => $session->id]);
        StudentAttendance::factory()->absent()->create(['enrollment_id' => $booked->id]);

        $response = $this->actingAs($user)->get("/cms/operations/attendance/{$session->id}");
        $students = $response->getOriginalContent()->getData()['page']['props']['students'];

        $this->assertCount(1, $students);
        $this->assertSame($booked->id, $students[0]['enrollment_id']);
        $this->assertSame('absent', $students[0]['status']);
    }

    public function test_coach_cannot_open_another_coaches_roster(): void
    {
        [$user] = $this->coach();
        $session = ClassSession::factory()->create(['session_date' => now()->toDateString()]);

        $this->actingAs($user)->get("/cms/operations/attendance/{$session->id}")->assertForbidden();
    }

    public function test_reports_sum_taught_hours_per_coach_for_the_month(): void
    {
        $admin = $this->admin();
        [, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile, ['session_date' => now()->startOfMonth()->toDateString()]);
        TeacherAttendance::create([
            'class_session_id' => $session->id,
            'coach_profile_id' => $coachProfile->id,
            'checked_in_at' => now()->startOfMonth()->setTime(8, 0),
            'checked_out_at' => now()->startOfMonth()->setTime(9, 30),
        ]);

        $response = $this->actingAs($admin)->get('/cms/operations/attendance/reports');
        $taught = $response->getOriginalContent()->getData()['page']['props']['taughtHours'];

        $this->assertCount(1, $taught);
        $this->assertSame(1, $taught[0]['sessions']);
        $this->assertSame(90, $taught[0]['minutes']);
    }

    public function test_reports_ignore_shifts_that_were_never_checked_out(): void
    {
        $admin = $this->admin();
        [, $coachProfile] = $this->coach();
        $session = $this->sessionFor($coachProfile, ['session_date' => now()->startOfMonth()->toDateString()]);
        TeacherAttendance::create([
            'class_session_id' => $session->id,
            'coach_profile_id' => $coachProfile->id,
            'checked_in_at' => now()->startOfMonth()->setTime(8, 0),
        ]);

        $response = $this->actingAs($admin)->get('/cms/operations/attendance/reports');

        $this->assertCount(0, $response->getOriginalContent()->getData()['page']['props']['taughtHours']);
    }

    public function test_reports_compute_the_student_attendance_rate(): void
    {
        $admin = $this->admin();
        $studentProfile = StudentProfile::factory()->create();
        $date = now()->startOfMonth()->toDateString();
        $branchId = ClassSession::factory()->create(['session_date' => $date])->branch_id;

        foreach (['present', 'late', 'absent', 'present'] as $status) {
            $session = ClassSession::factory()->create(['session_date' => $date, 'branch_id' => $branchId]);
            $enrollment = Enrollment::factory()->create([
                'class_session_id' => $session->id,
                'student_profile_id' => $studentProfile->id,
            ]);
            StudentAttendance::factory()->create(['enrollment_id' => $enrollment->id, 'status' => $status]);
        }

        $response = $this->actingAs($admin)
            ->withUnencryptedCookie('branch_id', (string) $branchId)
            ->get('/cms/operations/attendance/reports');
        $rates = $response->getOriginalContent()->getData()['page']['props']['attendanceRates'];

        $this->assertCount(1, $rates);
        $this->assertSame(4, $rates[0]['total']);
        $this->assertSame(1, $rates[0]['absent']);
        $this->assertSame(75, $rates[0]['rate']);
    }

    public function test_reports_exclude_months_other_than_the_selected_one(): void
    {
        $admin = $this->admin();
        $session = ClassSession::factory()->create(['session_date' => now()->startOfMonth()->toDateString()]);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);
        StudentAttendance::factory()->create(['enrollment_id' => $enrollment->id]);

        $month = now()->subMonth()->format('Y-m');
        $response = $this->actingAs($admin)->get("/cms/operations/attendance/reports?month={$month}");
        $props = $response->getOriginalContent()->getData()['page']['props'];

        $this->assertSame($month, $props['month']);
        $this->assertCount(0, $props['attendanceRates']);
    }

    public function test_reports_keep_the_selected_month_late_in_a_long_month(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));

        $admin = $this->admin();
        $session = ClassSession::factory()->create(['session_date' => '2026-09-15']);
        $enrollment = Enrollment::factory()->create(['class_session_id' => $session->id]);
        StudentAttendance::factory()->create(['enrollment_id' => $enrollment->id]);

        $response = $this->actingAs($admin)
            ->withUnencryptedCookie('branch_id', (string) $session->branch_id)
            ->get('/cms/operations/attendance/reports?month=2026-09');
        $props = $response->getOriginalContent()->getData()['page']['props'];

        $this->assertSame('2026-09', $props['month']);
        $this->assertCount(1, $props['attendanceRates']);
    }

    private function coach(): array
    {
        $user = User::factory()->create(['role' => 'coach']);
        $coachProfile = CoachProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $coachProfile];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function sessionFor(CoachProfile $coachProfile, array $attributes = []): ClassSession
    {
        return ClassSession::factory()->create(array_merge([
            'coach_profile_id' => $coachProfile->id,
            'session_date' => now()->toDateString(),
        ], $attributes));
    }
}
