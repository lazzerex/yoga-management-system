<?php

namespace App\Modules\Operations\Tuition\Actions;

use App\Models\Invoice;
use App\Models\Payment;

/** Shared by the invoice board and the dashboard so both quote the same figures. */
class TuitionStatsAction
{
    public function execute(?int $branchId): array
    {
        $scope = fn ($query) => $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $open = Invoice::query()->tap($scope)->open()->withSum('recordedPayments', 'amount')->get();

        return [
            'collected' => (int) Payment::recorded()->whereHas('invoice', fn ($q) => $q->tap($scope))
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'outstanding' => (int) $open->sum(fn (Invoice $invoice) => $invoice->balance()),
            'openCount' => $open->count(),
            'overdueCount' => Invoice::query()->tap($scope)->overdue()->count(),
        ];
    }
}
