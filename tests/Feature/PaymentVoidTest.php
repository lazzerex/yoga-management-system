<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentVoidTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_voiding_returns_the_amount_to_the_balance_and_reopens_the_invoice(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(1000000);

        $this->pay($admin, $invoice, 1000000);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance());

        $payment = $invoice->payments()->firstOrFail();
        $this->void($admin, $invoice, $payment)->assertRedirect();

        $invoice = $invoice->fresh();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(0, $invoice->paidAmount());
        $this->assertSame(1000000, $invoice->balance());
    }

    public function test_a_part_payment_voided_leaves_the_rest_counted(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(1000000);

        $this->pay($admin, $invoice, 400000);
        $this->pay($admin, $invoice, 300000);
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame(700000, $invoice->fresh()->paidAmount());

        $this->void($admin, $invoice, $invoice->payments()->orderBy('id')->firstOrFail());

        $invoice = $invoice->fresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame(300000, $invoice->paidAmount());
        $this->assertSame(700000, $invoice->balance());
    }

    public function test_a_voided_payment_keeps_its_row_its_audit_trail_and_its_proof(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(500000);

        $this->pay($admin, $invoice, 500000, UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'));
        $payment = $invoice->payments()->firstOrFail();
        $mediaId = $payment->getFirstMedia('proof')->id;

        $this->void($admin, $invoice, $payment, 'Cheque bounced.');

        $payment = $payment->fresh();
        $this->assertSame('voided', $payment->status);
        $this->assertSame(500000, $payment->amount);
        $this->assertSame('Cheque bounced.', $payment->void_reason);
        $this->assertSame($admin->id, $payment->voided_by_user_id);
        $this->assertNotNull($payment->voided_at);

        $this->assertDatabaseHas('media', ['id' => $mediaId]);
        $this->assertSame($mediaId, $payment->getFirstMedia('proof')->id);
        $this->actingAs($admin)->get("/cms/operations/files/{$mediaId}")->assertOk();
    }

    public function test_a_voided_payment_cannot_be_voided_again(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(500000);

        $this->pay($admin, $invoice, 500000);
        $payment = $invoice->payments()->firstOrFail();

        $this->void($admin, $invoice, $payment)->assertRedirect();
        $this->void($admin, $invoice, $payment)->assertSessionHasErrors('action');

        $this->assertSame(1, Payment::where('status', 'voided')->count());
    }

    public function test_voiding_needs_a_reason_and_the_tuition_manage_permission(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(500000);
        $this->pay($admin, $invoice, 500000);
        $payment = $invoice->payments()->firstOrFail();
        $url = "/cms/operations/tuition-fees/{$invoice->id}/payments/{$payment->id}/void";

        $this->actingAs($admin)->post($url, ['void_reason' => ''])->assertSessionHasErrors('void_reason');
        $this->actingAs(User::factory()->create(['role' => 'coach']))->post($url, ['void_reason' => 'no'])->assertForbidden();

        $this->assertSame('recorded', $payment->fresh()->status);
    }

    public function test_a_payment_cannot_be_voided_through_another_invoice(): void
    {
        $admin = $this->admin();
        $mine = $this->invoice(500000);
        $other = $this->invoice(500000);
        $this->pay($admin, $mine, 500000);
        $payment = $mine->payments()->firstOrFail();

        $this->actingAs($admin)
            ->post("/cms/operations/tuition-fees/{$other->id}/payments/{$payment->id}/void", ['void_reason' => 'Wrong invoice.'])
            ->assertNotFound();

        $this->assertSame('recorded', $payment->fresh()->status);
    }

    public function test_an_invoice_holding_a_voided_payment_still_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(500000);
        $this->pay($admin, $invoice, 500000);
        $this->void($admin, $invoice, $invoice->payments()->firstOrFail());

        $this->actingAs($admin)->delete("/cms/operations/tuition-fees/{$invoice->id}")->assertSessionHasErrors('action');
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_a_voided_payment_leaves_the_collected_total_and_the_membership_balance(): void
    {
        $admin = $this->admin();
        $branch = Branch::factory()->create();
        $student = $this->student();
        $invoice = $this->invoice(600000, $student, $branch);

        $this->pay($admin, $invoice, 600000);

        $props = $this->actingAs($admin)->withUnencryptedCookie('branch_id', $branch->id)
            ->get('/cms/operations/tuition-fees')->viewData('page')['props'];
        $this->assertSame(600000, $props['stats']['collected']);
        $this->assertSame(0, $props['stats']['outstanding']);

        $this->void($admin, $invoice, $invoice->payments()->firstOrFail());

        $props = $this->actingAs($admin)->withUnencryptedCookie('branch_id', $branch->id)
            ->get('/cms/operations/tuition-fees')->viewData('page')['props'];
        $this->assertSame(0, $props['stats']['collected']);
        $this->assertSame(600000, $props['stats']['outstanding']);

        $membership = $this->actingAs($student->user)->get('/cms/member/my-membership')->viewData('page')['props'];
        $this->assertSame(600000, $membership['outstanding']);
    }

    public function test_voiding_frees_the_balance_for_a_replacement_payment(): void
    {
        $admin = $this->admin();
        $invoice = $this->invoice(500000);

        $this->pay($admin, $invoice, 500000);
        $this->void($admin, $invoice, $invoice->payments()->firstOrFail());

        $this->pay($admin, $invoice, 500000)->assertRedirect();

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(500000, $invoice->fresh()->paidAmount());
        $this->assertSame(2, $invoice->payments()->count());
    }

    private function pay(User $admin, Invoice $invoice, int $amount, ?UploadedFile $proof = null)
    {
        return $this->actingAs($admin)->post("/cms/operations/tuition-fees/{$invoice->id}/payments", array_filter([
            'amount' => $amount,
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
            'proof' => $proof,
        ]));
    }

    private function void(User $admin, Invoice $invoice, Payment $payment, string $reason = 'Recorded twice.')
    {
        return $this->actingAs($admin)
            ->post("/cms/operations/tuition-fees/{$invoice->id}/payments/{$payment->id}/void", ['void_reason' => $reason]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function student(): StudentProfile
    {
        return StudentProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
        ]);
    }

    private function invoice(int $total, ?StudentProfile $student = null, ?Branch $branch = null): Invoice
    {
        return Invoice::factory()->create([
            'student_profile_id' => $student?->id ?? $this->student()->id,
            'branch_id' => $branch?->id ?? Branch::factory(),
            'total_amount' => $total,
        ]);
    }
}
