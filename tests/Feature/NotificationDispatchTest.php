<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Operations\ClassSession\Actions\UpdateClassSessionAction;
use App\Modules\Operations\Enrollment\Actions\CancelEnrollmentAction;
use App\Modules\Operations\LessonPlan\Actions\ReviewLessonPlanAction;
use App\Modules\Operations\LessonPlan\Actions\SubmitLessonPlanAction;
use App\Notifications\ClassCancelledNotification;
use App\Notifications\ClassReminderNotification;
use App\Notifications\EnrollmentCancelledByStaffNotification;
use App\Notifications\EnrollmentPromotedNotification;
use App\Notifications\LessonPlanReviewedNotification;
use App\Notifications\LessonPlanSubmittedNotification;
use App\Notifications\MemberRegisteredNotification;
use App\Notifications\TuitionDueSoonNotification;
use App\Notifications\TuitionOverdueNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_promoted_member_is_told_they_have_a_place(): void
    {
        Notification::fake();

        $session = ClassSession::factory()->create(['session_date' => now()->addDays(5)->toDateString()]);
        $booked = Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);
        [$waitingUser, $waitingProfile] = $this->member();
        Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $waitingProfile->id,
            'status' => 'waitlisted',
        ]);

        app(CancelEnrollmentAction::class)->execute($booked);

        Notification::assertSentTo($waitingUser, EnrollmentPromotedNotification::class);
        $this->assertDatabaseHas('enrollments', ['student_profile_id' => $waitingProfile->id, 'status' => 'booked']);
    }

    public function test_cancelling_without_a_waiting_list_notifies_nobody(): void
    {
        Notification::fake();

        $session = ClassSession::factory()->create(['session_date' => now()->addDays(5)->toDateString()]);
        $booked = Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);

        app(CancelEnrollmentAction::class)->execute($booked);

        Notification::assertNothingSent();
    }

    public function test_a_member_cancelling_their_own_booking_is_not_told_staff_did_it(): void
    {
        Notification::fake();

        $session = ClassSession::factory()->create(['session_date' => now()->addDays(5)->toDateString()]);
        [$user, $profile] = $this->member();
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $profile->id,
            'status' => 'booked',
        ]);

        $this->actingAs($user)->delete("/cms/member/enrollments/{$enrollment->id}");

        Notification::assertNotSentTo($user, EnrollmentCancelledByStaffNotification::class);
    }

    public function test_a_staff_cancellation_tells_the_member_who_lost_the_place(): void
    {
        Notification::fake();

        $session = ClassSession::factory()->create(['session_date' => now()->addDays(5)->toDateString()]);
        [$user, $profile] = $this->member();
        $enrollment = Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $profile->id,
            'status' => 'booked',
        ]);

        $this->actingAs($this->admin())->delete("/cms/operations/enrollments/{$enrollment->id}");

        Notification::assertSentTo($user, EnrollmentCancelledByStaffNotification::class);
    }

    public function test_cancelling_a_session_tells_its_booked_members_and_its_coach(): void
    {
        Notification::fake();

        [$coachUser, $coachProfile] = $this->coach();
        $session = ClassSession::factory()->create([
            'coach_profile_id' => $coachProfile->id,
            'status' => 'scheduled',
        ]);
        [$bookedUser, $bookedProfile] = $this->member();
        [$cancelledUser, $cancelledProfile] = $this->member();
        Enrollment::factory()->create(['class_session_id' => $session->id, 'student_profile_id' => $bookedProfile->id, 'status' => 'booked']);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'student_profile_id' => $cancelledProfile->id, 'status' => 'cancelled']);

        app(UpdateClassSessionAction::class)->execute($session, ['status' => 'cancelled']);

        Notification::assertSentTo($bookedUser, ClassCancelledNotification::class);
        Notification::assertSentTo($coachUser, ClassCancelledNotification::class);
        Notification::assertNotSentTo($cancelledUser, ClassCancelledNotification::class);
    }

    public function test_resaving_an_already_cancelled_session_sends_nothing(): void
    {
        $session = ClassSession::factory()->create(['status' => 'cancelled']);
        Enrollment::factory()->create(['class_session_id' => $session->id, 'status' => 'booked']);

        Notification::fake();

        app(UpdateClassSessionAction::class)->execute($session, ['status' => 'cancelled']);

        Notification::assertNothingSent();
    }

    public function test_a_submitted_lesson_plan_reaches_reviewers_by_permission_not_role(): void
    {
        Notification::fake();

        $reviewer = $this->admin();
        [$coachUser, $coachProfile] = $this->coach();
        $plan = LessonPlan::factory()->create(['coach_profile_id' => $coachProfile->id, 'status' => 'draft']);

        app(SubmitLessonPlanAction::class)->execute($plan);

        Notification::assertSentTo($reviewer, LessonPlanSubmittedNotification::class);
        Notification::assertNotSentTo($coachUser, LessonPlanSubmittedNotification::class);
    }

    public function test_a_reviewed_lesson_plan_reaches_the_owning_coach(): void
    {
        Notification::fake();

        $reviewer = $this->admin();
        [$coachUser, $coachProfile] = $this->coach();
        $plan = LessonPlan::factory()->create(['coach_profile_id' => $coachProfile->id, 'status' => 'pending']);

        app(ReviewLessonPlanAction::class)->execute($plan, ['action' => 'approved', 'comment' => null], $reviewer);

        Notification::assertSentTo($coachUser, LessonPlanReviewedNotification::class);
    }

    public function test_a_new_registration_reaches_holders_of_the_user_permission(): void
    {
        Notification::fake();

        $admin = $this->admin();
        [$member] = $this->member();

        $this->post('/cms/register', [
            'name' => 'New Person',
            'username' => 'newperson',
            'email' => 'new@example.test',
            'password' => 'password-is-long',
            'password_confirmation' => 'password-is-long',
        ]);

        Notification::assertSentTo($admin, MemberRegisteredNotification::class);
        Notification::assertNotSentTo($member, MemberRegisteredNotification::class);
    }

    public function test_the_tuition_command_reminds_about_due_and_overdue_invoices(): void
    {
        Notification::fake();

        [$dueUser, $dueProfile] = $this->member();
        [$overdueUser, $overdueProfile] = $this->member();
        Invoice::factory()->create([
            'student_profile_id' => $dueProfile->id,
            'due_date' => today()->addDays(3),
            'status' => 'unpaid',
        ]);
        Invoice::factory()->overdue()->create([
            'student_profile_id' => $overdueProfile->id,
            'status' => 'unpaid',
        ]);

        $this->artisan('notify:tuition-due')->assertSuccessful();

        Notification::assertSentTo($dueUser, TuitionDueSoonNotification::class);
        Notification::assertSentTo($overdueUser, TuitionOverdueNotification::class);
    }

    public function test_a_paid_invoice_is_never_chased(): void
    {
        Notification::fake();

        [$user, $profile] = $this->member();
        Invoice::factory()->create([
            'student_profile_id' => $profile->id,
            'due_date' => today()->addDays(3),
            'status' => 'paid',
        ]);

        $this->artisan('notify:tuition-due')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_running_the_tuition_command_twice_in_a_day_sends_once(): void
    {
        Notification::fake();

        [$user, $profile] = $this->member();
        Invoice::factory()->overdue()->create(['student_profile_id' => $profile->id, 'status' => 'unpaid']);

        $this->artisan('notify:tuition-due')->assertSuccessful();
        $this->artisan('notify:tuition-due')->assertSuccessful();

        Notification::assertSentToTimes($user, TuitionOverdueNotification::class, 1);
        $this->assertSame(1, DB::table('notification_dispatches')->count());
    }

    public function test_a_dry_run_lists_recipients_and_sends_nothing(): void
    {
        Notification::fake();

        [$user, $profile] = $this->member();
        Invoice::factory()->overdue()->create(['student_profile_id' => $profile->id, 'status' => 'unpaid']);

        $this->artisan('notify:tuition-due --dry-run')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(0, DB::table('notification_dispatches')->count());
    }

    public function test_the_limit_flag_caps_the_first_live_run(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $ignored) {
            [$user, $profile] = $this->member();
            Invoice::factory()->overdue()->create(['student_profile_id' => $profile->id, 'status' => 'unpaid']);
        }

        $this->artisan('notify:tuition-due --limit=1')->assertSuccessful();

        $this->assertSame(1, DB::table('notification_dispatches')->count());
    }

    public function test_the_class_command_reminds_tomorrows_members_and_coach_once(): void
    {
        Notification::fake();

        [$coachUser, $coachProfile] = $this->coach();
        $session = ClassSession::factory()->create([
            'coach_profile_id' => $coachProfile->id,
            'session_date' => today()->addDay()->toDateString(),
            'status' => 'scheduled',
        ]);
        [$memberUser, $memberProfile] = $this->member();
        Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $memberProfile->id,
            'status' => 'booked',
        ]);

        $this->artisan('notify:upcoming-classes')->assertSuccessful();
        $this->artisan('notify:upcoming-classes')->assertSuccessful();

        Notification::assertSentToTimes($memberUser, ClassReminderNotification::class, 1);
        Notification::assertSentToTimes($coachUser, ClassReminderNotification::class, 1);
    }

    public function test_a_class_further_out_than_tomorrow_is_not_reminded(): void
    {
        Notification::fake();

        $session = ClassSession::factory()->create([
            'session_date' => today()->addDays(4)->toDateString(),
            'status' => 'scheduled',
        ]);
        [$user, $profile] = $this->member();
        Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $profile->id,
            'status' => 'booked',
        ]);

        $this->artisan('notify:upcoming-classes')->assertSuccessful();

        Notification::assertNotSentTo($user, ClassReminderNotification::class);
    }

    private function member(): array
    {
        $user = User::factory()->create(['role' => 'member']);

        return [$user, StudentProfile::factory()->create(['user_id' => $user->id])];
    }

    private function coach(): array
    {
        $user = User::factory()->create(['role' => 'coach']);

        return [$user, CoachProfile::factory()->create(['user_id' => $user->id])];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
