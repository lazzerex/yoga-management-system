<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\TuitionPlan;
use Illuminate\Validation\ValidationException;

class DeleteTuitionPlanAction
{
    public function execute(TuitionPlan $tuitionPlan): void
    {
        if ($tuitionPlan->invoiceItems()->exists()) {
            throw ValidationException::withMessages([
                'action' => __('flash.tuitionPlanInvoiced'),
            ]);
        }

        $tuitionPlan->delete();
    }
}
