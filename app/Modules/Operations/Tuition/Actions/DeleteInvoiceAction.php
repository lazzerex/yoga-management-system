<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class DeleteInvoiceAction
{
    public function execute(Invoice $invoice): void
    {
        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.invoiceHasPayments'),
            ]);
        }

        // A waived invoice has no payments but can still have been booked on, and a
        // cancelled booking keeps its line reference, so any reference at all counts.
        if ($invoice->items()->has('enrollments')->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.invoiceHasBookings'),
            ]);
        }

        $invoice->delete();
    }
}
