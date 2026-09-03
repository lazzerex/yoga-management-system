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

        $invoice->delete();
    }
}
