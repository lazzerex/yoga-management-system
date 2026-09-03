<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentAction
{
    public function execute(Invoice $invoice, array $validated, User $recordedBy): Payment
    {
        $amount = (int) $validated['amount'];

        return DB::transaction(function () use ($invoice, $validated, $recordedBy, $amount) {
            // Locked and re-read inside the transaction: two clerks posting at once
            // must not each see the same balance and together overpay the invoice.
            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'waived') {
                throw ValidationException::withMessages([
                    'action' => __('flash.invoiceWaivedNoPayment'),
                ]);
            }

            if ($amount > $locked->balance()) {
                throw ValidationException::withMessages([
                    'action' => __('flash.paymentExceedsBalance'),
                ]);
            }

            $payment = $locked->payments()->create([
                'recorded_by_user_id' => $recordedBy->id,
                'amount' => $amount,
                'method' => $validated['method'],
                'paid_at' => $validated['paid_at'],
                'reference' => $validated['reference'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            $locked->update(['status' => $this->statusFor($locked)]);

            return $payment;
        });
    }

    private function statusFor(Invoice $invoice): string
    {
        $paid = (int) $invoice->payments()->sum('amount');

        return match (true) {
            $paid >= $invoice->total_amount => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };
    }
}
