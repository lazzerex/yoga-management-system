<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use App\Modules\Operations\Tuition\Actions\CreateInvoiceAction;
use App\Modules\Operations\Tuition\Actions\RecordPaymentAction;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $plans = TuitionPlan::orderBy('id')->get();
        $fallbackBranch = Branch::active()->orderBy('name')->first();
        $students = StudentProfile::with('enrollments.classSession')->get();

        if (! $admin || $plans->isEmpty() || ! $fallbackBranch) {
            return;
        }

        $create = app(CreateInvoiceAction::class);
        $record = app(RecordPaymentAction::class);

        foreach ($students->values() as $index => $student) {
            // Shift a place every four students, or the status cycle below would
            // pin each status to one plan and no monthly plan would ever read paid.
            $plan = $plans[($index + intdiv($index, 4)) % $plans->count()];
            $branchId = $student->enrollments->first()?->classSession?->branch_id ?? $fallbackBranch->id;
            $overdue = $index % 4 === 3;
            $issuedAt = $overdue ? now()->subDays(45) : now()->subDays(($index % 20) + 1);

            $invoice = $create->execute([
                'student_profile_id' => $student->id,
                'branch_id' => $branchId,
                'issued_at' => $issuedAt->toDateString(),
                'due_date' => $issuedAt->copy()->addDays(14)->toDateString(),
                'note' => null,
                'items' => [
                    ['tuition_plan_id' => $plan->id, 'description' => null, 'quantity' => 1, 'unit_price' => null],
                ],
            ]);

            $this->settle($record, $invoice, $index, $admin);
        }
    }

    /** One invoice of each shape so every status badge and the debt report have real data. */
    private function settle(RecordPaymentAction $record, Invoice $invoice, int $index, User $admin): void
    {
        $share = match ($index % 4) {
            0 => 0,
            1 => 0.5,
            2 => 1.0,
            default => 0,
        };

        if ($share === 0) {
            return;
        }

        $record->execute($invoice, [
            'amount' => (int) round($invoice->total_amount * $share),
            'method' => $index % 2 === 0 ? 'cash' : 'transfer',
            // Kept inside the current month so the "collected this month" tile has demo data.
            'paid_at' => now()->subDays(2)->max(now()->startOfMonth())->toDateTimeString(),
            'reference' => null,
            'note' => null,
        ], $admin);
    }
}
