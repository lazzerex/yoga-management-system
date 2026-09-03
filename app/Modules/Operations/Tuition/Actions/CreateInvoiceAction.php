<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use App\Models\TuitionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvoiceAction
{
    public function execute(array $validated): Invoice
    {
        return DB::transaction(function () use ($validated) {
            $plans = TuitionPlan::whereIn('id', array_filter(array_column($validated['items'], 'tuition_plan_id')))
                ->get()
                ->keyBy('id');

            $issuedAt = Carbon::parse($validated['issued_at']);
            $lines = array_map(fn (array $item) => $this->line($item, $plans->get($item['tuition_plan_id'] ?? null), $issuedAt), $validated['items']);

            $invoice = Invoice::create([
                'student_profile_id' => $validated['student_profile_id'],
                'branch_id' => $validated['branch_id'],
                'invoice_number' => 'PENDING',
                'issued_at' => $validated['issued_at'],
                'due_date' => $validated['due_date'],
                'status' => 'unpaid',
                'total_amount' => array_sum(array_column($lines, 'line_total')),
                'note' => $validated['note'] ?? null,
            ]);

            // Numbered off the primary key so the sequence cannot collide under concurrency.
            $invoice->update([
                'invoice_number' => 'INV-'.$issuedAt->format('Ym').'-'.str_pad((string) $invoice->id, 4, '0', STR_PAD_LEFT),
            ]);

            $invoice->items()->createMany($lines);

            return $invoice;
        });
    }

    private function line(array $item, ?TuitionPlan $plan, Carbon $issuedAt): array
    {
        $quantity = (int) $item['quantity'];
        $unitPrice = (int) ($plan?->price_amount ?? $item['unit_price']);
        $validFrom = $plan ? $issuedAt->toDateString() : null;

        return [
            'tuition_plan_id' => $plan?->id,
            'description' => $plan?->name ?? $item['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
            'valid_from' => $validFrom,
            'valid_until' => $plan?->duration_days ? $issuedAt->copy()->addDays($plan->duration_days * $quantity)->toDateString() : null,
            'sessions_granted' => $plan?->session_count ? $plan->session_count * $quantity : null,
        ];
    }
}
