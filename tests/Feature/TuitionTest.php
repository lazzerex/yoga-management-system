<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TuitionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_an_invoice_copies_price_and_entitlement_off_the_plan(): void
    {
        $plan = TuitionPlan::factory()->pack(10)->create(['price_amount' => 1500000, 'duration_days' => 90]);
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post('/cms/operations/tuition-fees', $this->invoicePayload($student, ['items' => [
                ['tuition_plan_id' => $plan->id, 'quantity' => 2],
            ]]))
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $item = $invoice->items()->firstOrFail();

        $this->assertSame(3000000, $invoice->total_amount);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame($plan->name, $item->description);
        $this->assertSame(1500000, $item->unit_price);
        $this->assertSame(20, $item->sessions_granted);
        $this->assertSame(today()->addDays(180)->toDateString(), $item->valid_until->toDateString());
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
    }

    public function test_a_partial_payment_then_the_rest_moves_the_invoice_to_paid(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(1000000);

        $this->actingAs($admin)
            ->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(400000))
            ->assertRedirect();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame(600000, $invoice->fresh()->balance());

        $this->actingAs($admin)
            ->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(600000))
            ->assertRedirect();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance());
    }

    public function test_a_payment_larger_than_the_balance_is_refused(): void
    {
        $invoice = $this->invoice(1000000);

        // Reported on the reserved 'action' key so AppLayout surfaces it as a toast.
        $this->actingAs($this->admin())
            ->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(1000001))
            ->assertSessionHasErrors('action');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_a_waived_invoice_refuses_payment_posted_straight_at_the_route(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(1000000);

        $this->actingAs($admin)->post("/cms/operations/tuition-fees/{$invoice->id}/waive")->assertRedirect();
        $this->assertSame('waived', $invoice->fresh()->status);

        // The UI never renders the payment panel for a waived invoice; the action must refuse anyway.
        $this->actingAs($admin)
            ->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(1000))
            ->assertSessionHasErrors('action');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_an_invoice_with_a_payment_can_neither_be_waived_nor_deleted(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(1000000);

        $this->actingAs($admin)->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(500000));

        $this->actingAs($admin)->post("/cms/operations/tuition-fees/{$invoice->id}/waive")->assertSessionHasErrors('action');
        $this->actingAs($admin)->delete("/cms/operations/tuition-fees/{$invoice->id}")->assertSessionHasErrors('action');

        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_overdue_is_derived_from_the_due_date_and_never_stored(): void
    {
        $overdue = Invoice::factory()->overdue()->create(['total_amount' => 500000]);
        $future = Invoice::factory()->create(['due_date' => today()->addWeek(), 'total_amount' => 500000]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertSame('overdue', $overdue->displayStatus());
        $this->assertSame('unpaid', $overdue->status);
        $this->assertFalse($future->isOverdue());
        $this->assertSame([$overdue->id], Invoice::overdue()->pluck('id')->all());
    }

    public function test_a_waived_invoice_is_never_counted_as_overdue(): void
    {
        $invoice = Invoice::factory()->overdue()->create(['status' => 'waived', 'total_amount' => 500000]);

        $this->assertFalse($invoice->isOverdue());
        $this->assertSame(0, Invoice::overdue()->count());
    }

    public function test_the_invoice_list_and_stats_follow_the_current_branch(): void
    {
        $here = Branch::factory()->create();
        $there = Branch::factory()->create();
        $mine = Invoice::factory()->create(['branch_id' => $here->id, 'total_amount' => 700000]);
        Invoice::factory()->create(['branch_id' => $there->id, 'total_amount' => 900000]);

        $props = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $here->id)
            ->get('/cms/operations/tuition-fees')
            ->viewData('page')['props'];

        $this->assertSame([$mine->id], array_column($props['invoices']['data'], 'id'));
        $this->assertSame(700000, $props['stats']['outstanding']);
    }

    public function test_a_coach_cannot_reach_tuition_at_all(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $invoice = $this->invoice(100000);

        $this->actingAs($coach)->get('/cms/operations/tuition-fees')->assertForbidden();
        $this->actingAs($coach)->get('/cms/operations/tuition-fees/create')->assertForbidden();
        $this->actingAs($coach)->get('/cms/operations/tuition-fees/plans')->assertForbidden();
        $this->actingAs($coach)->get("/cms/operations/tuition-fees/{$invoice->id}")->assertForbidden();
        $this->actingAs($coach)->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(1000))->assertForbidden();
    }

    public function test_a_member_reaches_membership_but_not_the_admin_tuition_pages(): void
    {
        $student = $this->student();
        $invoice = $this->invoice(100000, $student);

        $this->actingAs($student->user)->get('/cms/operations/tuition-fees')->assertForbidden();
        $this->actingAs($student->user)->get("/cms/operations/tuition-fees/{$invoice->id}")->assertForbidden();
        $this->actingAs($student->user)->get('/cms/member/my-membership')->assertOk();
    }

    public function test_membership_shows_only_the_signed_in_members_invoices(): void
    {
        $mine = $this->student();
        $theirs = $this->student();
        $own = $this->invoice(400000, $mine);
        $this->invoice(900000, $theirs);

        $props = $this->actingAs($mine->user)->get('/cms/member/my-membership')->viewData('page')['props'];

        $this->assertSame([$own->id], array_column($props['invoices'], 'id'));
        $this->assertSame(400000, $props['outstanding']);
    }

    public function test_membership_lists_an_entitlement_only_once_its_invoice_is_paid(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $plan = TuitionPlan::factory()->create(['price_amount' => 300000, 'duration_days' => 30]);

        $this->actingAs($admin)->post('/cms/operations/tuition-fees', $this->invoicePayload($student, ['items' => [
            ['tuition_plan_id' => $plan->id, 'quantity' => 1],
        ]]));
        $invoice = Invoice::firstOrFail();

        $props = $this->actingAs($student->user)->get('/cms/member/my-membership')->viewData('page')['props'];
        $this->assertSame([], $props['entitlements']);

        $this->actingAs($admin)->post("/cms/operations/tuition-fees/{$invoice->id}/payments", $this->paymentPayload(300000));

        $props = $this->actingAs($student->user)->get('/cms/member/my-membership')->viewData('page')['props'];
        $this->assertCount(1, $props['entitlements']);
        $this->assertSame($plan->name, $props['entitlements'][0]['description']);
    }

    public function test_the_export_streams_a_row_per_invoice(): void
    {
        $branch = Branch::factory()->create();
        $invoice = $this->invoice(250000, null, $branch);

        $response = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $branch->id)
            ->get('/cms/operations/tuition-fees/export')
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Invoice,Student,Branch,Issued,Due,Status,Total,Paid,Balance', $csv);
        $this->assertStringContainsString($invoice->invoice_number.',', $csv);
        $this->assertStringContainsString(',250000,0,250000', $csv);
    }

    public function test_a_branch_or_student_holding_invoices_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $student = $this->student();
        $branch = Branch::factory()->create();
        $this->invoice(100000, $student, $branch);

        $this->actingAs($admin)->delete("/cms/operations/branches/{$branch->id}")->assertSessionHasErrors('action');
        $this->actingAs($admin)->delete("/cms/operations/students/{$student->id}")->assertSessionHasErrors('action');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('student_profiles', ['id' => $student->id]);
    }

    public function test_a_tuition_plan_that_has_been_invoiced_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $plan = TuitionPlan::factory()->create();

        $this->actingAs($admin)->delete("/cms/operations/tuition-fees/plans/{$plan->id}")->assertRedirect();
        $this->assertDatabaseMissing('tuition_plans', ['id' => $plan->id]);

        $billed = TuitionPlan::factory()->create();
        $this->actingAs($admin)->post('/cms/operations/tuition-fees', $this->invoicePayload($this->student(), ['items' => [
            ['tuition_plan_id' => $billed->id, 'quantity' => 1],
        ]]));

        $this->actingAs($admin)->delete("/cms/operations/tuition-fees/plans/{$billed->id}")->assertSessionHasErrors('action');
        $this->assertDatabaseHas('tuition_plans', ['id' => $billed->id]);
    }

    public function test_a_custom_line_needs_its_own_description_and_price(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post('/cms/operations/tuition-fees', $this->invoicePayload($student, ['items' => [
                ['tuition_plan_id' => '', 'quantity' => 1],
            ]]))
            ->assertSessionHasErrors(['items.0.description', 'items.0.unit_price']);

        $this->actingAs($this->admin())
            ->post('/cms/operations/tuition-fees', $this->invoicePayload($student, ['items' => [
                ['tuition_plan_id' => '', 'description' => 'Workshop', 'quantity' => 2, 'unit_price' => 150000],
            ]]))
            ->assertRedirect();

        $this->assertDatabaseHas('invoice_items', [
            'description' => 'Workshop',
            'unit_price' => 150000,
            'line_total' => 300000,
            'tuition_plan_id' => null,
            'valid_until' => null,
        ]);
    }

    public function test_an_invoice_due_on_its_issue_date_is_not_overdue(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post('/cms/operations/tuition-fees', $this->invoicePayload($student, [
                'issued_at' => today()->toDateString(),
                'due_date' => today()->toDateString(),
            ]))
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();

        $this->assertFalse($invoice->isOverdue());
        $this->assertSame('unpaid', $invoice->displayStatus());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function student(): StudentProfile
    {
        $user = User::factory()->create(['role' => 'member']);

        return StudentProfile::factory()->create(['user_id' => $user->id]);
    }

    private function invoice(int $total, ?StudentProfile $student = null, ?Branch $branch = null): Invoice
    {
        return Invoice::factory()->create([
            'student_profile_id' => $student?->id ?? $this->student()->id,
            'branch_id' => $branch?->id ?? Branch::factory(),
            'total_amount' => $total,
        ]);
    }

    private function invoicePayload(StudentProfile $student, array $overrides = []): array
    {
        return array_merge([
            'student_profile_id' => $student->id,
            'branch_id' => Branch::factory()->create()->id,
            'issued_at' => today()->toDateString(),
            'due_date' => today()->addWeek()->toDateString(),
            'note' => null,
            'items' => [['tuition_plan_id' => TuitionPlan::factory()->create()->id, 'quantity' => 1]],
        ], $overrides);
    }

    private function paymentPayload(int $amount): array
    {
        return [
            'amount' => $amount,
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
            'reference' => null,
            'note' => null,
        ];
    }
}
