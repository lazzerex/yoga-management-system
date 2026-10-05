<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EntitlementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_member_with_no_invoice_cannot_book(): void
    {
        [$user] = $this->member();
        $session = $this->upcomingSession();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentNoEntitlement')]);

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_an_unpaid_invoice_grants_nothing(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, status: 'unpaid');
        $session = $this->upcomingSession();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentNoEntitlement')]);
    }

    public function test_a_part_paid_invoice_grants_nothing(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, status: 'partial');
        $session = $this->upcomingSession();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentNoEntitlement')]);
    }

    public function test_a_waived_invoice_grants_access_like_a_paid_one(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, status: 'waived');
        $session = $this->upcomingSession();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', ['status' => 'booked']);
    }

    public function test_a_one_off_charge_with_no_plan_grants_nothing(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, ['tuition_plan_id' => null, 'description' => 'Mat hire']);
        $session = $this->upcomingSession();

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentNoEntitlement')]);
    }

    public function test_an_unlimited_plan_books_without_consuming_a_quota(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student);
        $session = $this->upcomingSession();

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");

        $this->assertDatabaseHas('enrollments', ['invoice_item_id' => $line->id, 'status' => 'booked']);
        $this->assertNull($line->fresh()->sessionsRemaining());
    }

    public function test_booking_on_a_pack_spends_one_session(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, ['sessions_granted' => 3]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->assertSame(2, $line->fresh()->sessionsRemaining());
    }

    public function test_an_exhausted_pack_cannot_book_again(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, ['sessions_granted' => 1]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentEntitlementExhausted')]);

        $this->assertDatabaseCount('enrollments', 1);
    }

    /** The window is compared against the session date, not against today. */
    public function test_a_plan_valid_today_cannot_book_a_session_after_it_lapses(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, ['valid_until' => today()->addDays(3)->toDateString()]);
        $session = $this->upcomingSession(now()->addDays(10)->toDateString());

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasErrors(['action' => __('flash.enrollmentEntitlementExpired')]);
    }

    public function test_a_pack_sold_without_a_duration_never_expires(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, ['sessions_granted' => 5, 'valid_until' => null]);
        $session = $this->upcomingSession(now()->addYears(2)->toDateString());

        $this->actingAs($user)
            ->post("/cms/member/class-sessions/{$session->id}/enrollments")
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $line->fresh()->sessionsRemaining());
    }

    public function test_cancelling_a_booking_returns_the_session_to_the_pack(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, ['sessions_granted' => 3]);
        $session = $this->upcomingSession();

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");
        $this->assertSame(2, $line->fresh()->sessionsRemaining());

        $enrollment = Enrollment::firstOrFail();
        $this->actingAs($user)->delete("/cms/member/enrollments/{$enrollment->id}");

        $this->assertSame(3, $line->fresh()->sessionsRemaining());
    }

    /** A waitlisted place holds the member's own credit, so promotion resolves nothing. */
    public function test_a_waitlisted_place_holds_a_session_until_it_is_cancelled(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, ['sessions_granted' => 2]);
        $session = $this->upcomingSession();
        $session->update(['capacity' => 1]);
        Enrollment::factory()->create(['class_session_id' => $session->id]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");

        $mine = Enrollment::where('student_profile_id', $student->id)->firstOrFail();
        $this->assertSame('waitlisted', $mine->status);
        $this->assertSame(1, $line->fresh()->sessionsRemaining());

        $this->actingAs($user)->delete("/cms/member/enrollments/{$mine->id}");

        $this->assertSame(2, $line->fresh()->sessionsRemaining());
    }

    public function test_an_unlimited_plan_is_spent_before_a_pack(): void
    {
        [$user, $student] = $this->member();
        $pack = $this->grantPlan($student, ['sessions_granted' => 5]);
        $unlimited = $this->grantPlan($student);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->assertDatabaseHas('enrollments', ['invoice_item_id' => $unlimited->id]);
        $this->assertSame(5, $pack->fresh()->sessionsRemaining());
    }

    public function test_the_pack_closest_to_expiry_is_spent_first(): void
    {
        [$user, $student] = $this->member();
        $later = $this->grantPlan($student, ['sessions_granted' => 5, 'valid_until' => today()->addDays(60)->toDateString()]);
        $sooner = $this->grantPlan($student, ['sessions_granted' => 5, 'valid_until' => today()->addDays(10)->toDateString()]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->assertDatabaseHas('enrollments', ['invoice_item_id' => $sooner->id]);
        $this->assertSame(5, $later->fresh()->sessionsRemaining());
    }

    public function test_an_exhausted_pack_is_skipped_for_one_that_still_has_sessions(): void
    {
        [$user, $student] = $this->member();
        $spent = $this->grantPlan($student, ['sessions_granted' => 1, 'valid_until' => today()->addDays(10)->toDateString()]);
        $spare = $this->grantPlan($student, ['sessions_granted' => 1, 'valid_until' => today()->addDays(60)->toDateString()]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");
        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->assertSame(0, $spent->fresh()->sessionsRemaining());
        $this->assertSame(0, $spare->fresh()->sessionsRemaining());
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_an_invoice_whose_plan_was_booked_on_cannot_be_deleted(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, status: 'waived');
        $session = $this->upcomingSession();

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete("/cms/operations/tuition-fees/{$line->invoice_id}")
            ->assertSessionHasErrors(['action' => __('flash.invoiceHasBookings')]);

        $this->assertDatabaseHas('invoices', ['id' => $line->invoice_id]);
    }

    /** A cancelled booking still references the line, so the delete stays blocked. */
    public function test_a_cancelled_booking_still_blocks_deleting_its_invoice(): void
    {
        [$user, $student] = $this->member();
        $line = $this->grantPlan($student, status: 'waived');
        $session = $this->upcomingSession();

        $this->actingAs($user)->post("/cms/member/class-sessions/{$session->id}/enrollments");
        $this->actingAs($user)->delete('/cms/member/enrollments/'.Enrollment::firstOrFail()->id);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete("/cms/operations/tuition-fees/{$line->invoice_id}")
            ->assertSessionHasErrors(['action' => __('flash.invoiceHasBookings')]);
    }

    public function test_the_book_page_names_why_a_session_cannot_be_booked(): void
    {
        [$user] = $this->member();
        $this->upcomingSession();

        $this->actingAs($user)
            ->get('/cms/member/book')
            ->assertInertia(fn (Assert $page) => $page
                ->where('availableSessions.0.block_reason', 'flash.enrollmentNoEntitlement'));
    }

    public function test_the_book_page_leaves_the_reason_empty_when_the_member_can_book(): void
    {
        [$user, $student] = $this->member();
        $this->upcomingSession();
        $this->grantPlan($student);

        $this->actingAs($user)
            ->get('/cms/member/book')
            ->assertInertia(fn (Assert $page) => $page
                ->where('availableSessions.0.block_reason', null)
                ->has('entitlements', 1));
    }

    public function test_the_membership_page_reports_sessions_left_rather_than_sessions_sold(): void
    {
        [$user, $student] = $this->member();
        $this->grantPlan($student, ['sessions_granted' => 4]);

        $this->actingAs($user)->post("/cms/member/class-sessions/{$this->upcomingSession()->id}/enrollments");

        $this->actingAs($user)
            ->get('/cms/member/my-membership')
            ->assertInertia(fn (Assert $page) => $page
                ->where('entitlements.0.sessions_granted', 4)
                ->where('entitlements.0.sessions_remaining', 3));
    }

    /** @return array{0: User, 1: StudentProfile} */
    private function member(): array
    {
        $user = User::factory()->create(['role' => 'member']);

        return [$user, StudentProfile::factory()->create(['user_id' => $user->id])];
    }

    private function upcomingSession(?string $date = null): ClassSession
    {
        return ClassSession::factory()->create([
            'session_date' => $date ?? now()->addDays(3)->toDateString(),
            'capacity' => 10,
        ]);
    }

    /** Defaults to an unlimited plan that is live today; overrides make the other shapes. */
    public function test_a_plan_is_named_in_the_viewers_language(): void
    {
        [$user, $student] = $this->member();
        $plan = TuitionPlan::factory()->create(['name' => 'Drop-in Class', 'name_vi' => 'Buổi lẻ']);
        $this->grantPlan($student, ['tuition_plan_id' => $plan->id, 'description' => 'Drop-in Class']);

        $this->actingAs($user)->withUnencryptedCookie('locale', 'vi')
            ->get('/cms/member/book')
            ->assertInertia(fn (Assert $page) => $page->where('entitlements.0.description', 'Buổi lẻ'));

        $this->actingAs($user)->withUnencryptedCookie('locale', 'en')
            ->get('/cms/member/book')
            ->assertInertia(fn (Assert $page) => $page->where('entitlements.0.description', 'Drop-in Class'));
    }

    private function grantPlan(StudentProfile $student, array $line = [], string $status = 'paid'): InvoiceItem
    {
        $invoice = Invoice::factory()->create([
            'student_profile_id' => $student->id,
            // An existing branch, or the book page's branch filter hides the session under test.
            'branch_id' => Branch::orderBy('name')->value('id') ?? Branch::factory()->create()->id,
            'status' => $status,
        ]);

        return $invoice->items()->create(array_merge([
            'tuition_plan_id' => TuitionPlan::factory()->create()->id,
            'description' => 'Test plan',
            'quantity' => 1,
            'unit_price' => 0,
            'line_total' => 0,
            'valid_from' => today()->subDay()->toDateString(),
            'valid_until' => today()->addYear()->toDateString(),
            'sessions_granted' => null,
        ], $line));
    }
}
