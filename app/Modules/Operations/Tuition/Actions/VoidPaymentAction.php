<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Voiding is the only way to undo a payment. The row, its recorder, its date and
 * its proof file all stay; only the amount leaves the invoice balance.
 */
class VoidPaymentAction
{
    public function execute(Payment $payment, array $validated, User $voidedBy): Payment
    {
        return DB::transaction(function () use ($payment, $validated, $voidedBy) {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isVoided()) {
                throw ValidationException::withMessages([
                    'action' => __('flash.paymentAlreadyVoided'),
                ]);
            }

            $locked->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by_user_id' => $voidedBy->id,
                'void_reason' => $validated['void_reason'],
            ]);

            $invoice = Invoice::whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();
            $invoice->update(['status' => $invoice->statusFromPayments()]);

            return $locked;
        });
    }
}
