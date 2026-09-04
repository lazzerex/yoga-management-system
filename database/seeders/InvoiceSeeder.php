<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Models\User;
use App\Modules\Operations\Tuition\Actions\CreateInvoiceAction;
use App\Modules\Operations\Tuition\Actions\RecordPaymentAction;
use App\Modules\Operations\Tuition\Actions\VoidPaymentAction;
use App\Modules\Operations\Tuition\Actions\WaiveInvoiceAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InvoiceSeeder extends Seeder
{
    /** A year of billing, so the revenue chart and the ageing buckets both have shape. */
    private const MONTHS = 12;

    /** Branch ids that already have a payment in the current month. */
    private array $paidThisMonth = [];

    private array $extras = [
        'Mat and prop hire',
        'Workshop: backbends',
        'Private session add-on',
        'Late enrolment fee',
    ];

    private array $voidReasons = [
        'Duplicate transfer, refunded at the desk.',
        'Wrong invoice, re-posted against the correct one.',
        'Bank reversed the transfer.',
    ];

    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $plans = TuitionPlan::orderBy('id')->get();
        $branches = Branch::active()->orderBy('name')->get();
        $students = StudentProfile::orderBy('id')->get();

        if (! $admin || $plans->isEmpty() || $branches->isEmpty() || $students->isEmpty()) {
            return;
        }

        $create = app(CreateInvoiceAction::class);
        $record = app(RecordPaymentAction::class);
        $void = app(VoidPaymentAction::class);
        $waive = app(WaiveInvoiceAction::class);
        $sequence = 0;

        // Billing runs back through the year: older months are settled, the current
        // month is a mix, and a handful stay unpaid long enough to age.
        for ($monthsAgo = self::MONTHS - 1; $monthsAgo >= 0; $monthsAgo--) {
            $month = now()->copy()->subMonths($monthsAgo)->startOfMonth();
            $billedThisMonth = $students->count() >= 12
                ? (int) round($students->count() * ($monthsAgo > 6 ? 0.45 : 0.75))
                : $students->count();

            foreach ($students->take($billedThisMonth) as $student) {
                $sequence++;
                // Branch rotates independently of the student, so every branch has money.
                $branch = $branches[$sequence % $branches->count()];
                // Offset the plan cycle, or with four plans and four branches each
                // branch would only ever sell the same one.
                $plan = $plans[($sequence + intdiv($sequence, $plans->count())) % $plans->count()];
                // The current month is only as long as it has been so far, or most of
                // its invoices would be dated in the future and skipped.
                $span = $monthsAgo === 0 ? max(1, now()->day) : 24;
                $issuedAt = $month->copy()->addDays(($sequence * 3) % $span);

                $items = [
                    ['tuition_plan_id' => $plan->id, 'description' => null, 'quantity' => 1, 'unit_price' => null],
                ];

                // Every seventh invoice carries an extra one-off charge, so the line
                // table on the invoice page is not always a single row.
                if ($sequence % 7 === 0) {
                    $items[] = [
                        'tuition_plan_id' => null,
                        'description' => $this->extras[$sequence % count($this->extras)],
                        'quantity' => 1,
                        'unit_price' => [150000, 250000, 400000][$sequence % 3],
                    ];
                }

                $invoice = $create->execute([
                    'student_profile_id' => $student->id,
                    'branch_id' => $branch->id,
                    'issued_at' => $issuedAt->toDateString(),
                    'due_date' => $issuedAt->copy()->addDays(14)->toDateString(),
                    'note' => $sequence % 13 === 0 ? 'Renewal discussed at the desk.' : null,
                    'items' => $items,
                ]);

                // A few are written off instead of chased. Waiving takes a payment-free
                // invoice, so it happens before settlement — but never on the invoice
                // that is guaranteeing this branch some revenue this month.
                $guaranteed = $monthsAgo === 0 && ! in_array($branch->id, $this->paidThisMonth, true);

                if ($sequence % 23 === 0 && ! $guaranteed) {
                    $waive->execute($invoice);

                    continue;
                }

                $this->settle($record, $void, $invoice, $sequence, $monthsAgo, $admin, $issuedAt);
            }
        }
    }

    private function settle(
        RecordPaymentAction $record,
        VoidPaymentAction $void,
        Invoice $invoice,
        int $sequence,
        int $monthsAgo,
        User $admin,
        Carbon $issuedAt,
    ): void {
        // Anything older than two months is closed business; recent months carry the
        // partials, the arrears and the voided payments the demo needs.
        $share = match (true) {
            $monthsAgo > 2 => 1.0,
            $sequence % 5 === 0 => 0,
            $sequence % 5 === 1 => 0.5,
            default => 1.0,
        };

        // The first invoice a branch raises this month is always paid, or a quiet run
        // of arrears can leave a whole branch reading zero collected on the dashboard.
        if ($monthsAgo === 0 && ! in_array($invoice->branch_id, $this->paidThisMonth, true)) {
            $share = 1.0;
            $this->paidThisMonth[] = $invoice->branch_id;
        }

        if ($share === 0) {
            return;
        }

        $paidAt = $issuedAt->copy()->addDays(($sequence % 6) + 1)->setTime(9 + ($sequence % 8), 15);

        $payment = $record->execute($invoice, [
            'amount' => (int) round($invoice->total_amount * $share),
            'method' => $sequence % 3 === 0 ? 'transfer' : 'cash',
            'paid_at' => $paidAt->min(now())->toDateTimeString(),
            'reference' => $sequence % 3 === 0 ? 'TRF-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT) : null,
            'note' => null,
        ], $admin);

        // Voids are spread across the year rather than bunched in the current month.
        // Nothing here may share a period with the branch rotation, or one branch would
        // have every payment of a month voided.
        if ($sequence % 19 === 0 || ($monthsAgo === 0 && $sequence % 9 === 0)) {
            $void->execute($payment, [
                'void_reason' => $this->voidReasons[$sequence % count($this->voidReasons)],
            ], $admin);
        }
    }
}
