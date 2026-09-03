<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class WaiveInvoiceAction
{
    public function execute(Invoice $invoice): Invoice
    {
        if ($invoice->payments()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.invoiceHasPayments'),
            ]);
        }

        if ($invoice->status === 'waived') {
            throw ValidationException::withMessages([
                'action' => __('flash.invoiceAlreadyWaived'),
            ]);
        }

        $invoice->update(['status' => 'waived']);

        return $invoice;
    }
}
