<?php

namespace App\Modules\Dashboard\Actions;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Support\Collection;

/** Aggregates only: the invoice list and its filters stay on the tuition board. */
class FinancialsAction
{
    private const MONTHS = 12;

    public function execute(?int $branchId): array
    {
        $scope = fn ($query) => $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $payments = Payment::recorded()
            ->whereHas('invoice', fn ($q) => $q->tap($scope))
            ->where('paid_at', '>=', now()->startOfMonth()->subMonths(self::MONTHS - 1))
            ->get(['id', 'invoice_id', 'amount', 'method', 'paid_at']);

        return [
            'months' => self::MONTHS,
            'revenue' => $this->revenue($payments),
            'methodSplit' => $this->methodSplit($payments),
            'ageing' => $this->ageing($scope),
            'topPlans' => $this->topPlans($scope),
            'voidedThisMonth' => Payment::where('status', 'voided')
                ->whereHas('invoice', fn ($q) => $q->tap($scope))
                ->whereBetween('voided_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
    }

    /** @param  Collection<int, Payment>  $payments */
    private function revenue(Collection $payments): array
    {
        $from = now()->startOfMonth()->subMonths(self::MONTHS - 1);
        $byMonth = $payments
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn (Collection $group) => (int) $group->sum('amount'));

        return collect(range(0, self::MONTHS - 1))
            ->map(function (int $offset) use ($from, $byMonth) {
                $month = $from->copy()->addMonths($offset)->format('Y-m');

                return ['month' => $month, 'amount' => $byMonth[$month] ?? 0];
            })
            ->all();
    }

    /** @param  Collection<int, Payment>  $payments */
    private function methodSplit(Collection $payments): array
    {
        return collect(Payment::METHODS)
            ->map(fn (string $method) => [
                'method' => $method,
                'amount' => (int) $payments->where('method', $method)->sum('amount'),
            ])
            ->all();
    }

    /** Open balances bucketed by how long they have been overdue. */
    private function ageing(callable $scope): array
    {
        $buckets = [
            ['label' => 'current', 'min' => null, 'max' => 0],
            ['label' => 'd1to7', 'min' => 1, 'max' => 7],
            ['label' => 'd8to30', 'min' => 8, 'max' => 30],
            ['label' => 'd31plus', 'min' => 31, 'max' => null],
        ];

        $open = Invoice::query()->tap($scope)->open()->withSum('recordedPayments', 'amount')->get();

        return collect($buckets)
            ->map(function (array $bucket) use ($open) {
                $matching = $open->filter(function (Invoice $invoice) use ($bucket) {
                    $daysLate = $invoice->due_date->isFuture() ? 0 : (int) $invoice->due_date->diffInDays(today());

                    return ($bucket['min'] === null || $daysLate >= $bucket['min'])
                        && ($bucket['max'] === null || $daysLate <= $bucket['max']);
                });

                return [
                    'label' => $bucket['label'],
                    'invoices' => $matching->count(),
                    'amount' => (int) $matching->sum(fn (Invoice $invoice) => $invoice->balance()),
                ];
            })
            ->all();
    }

    private function topPlans(callable $scope): array
    {
        return InvoiceItem::with('tuitionPlan:id,name,name_vi')
            ->whereNotNull('tuition_plan_id')
            ->whereHas('invoice', fn ($q) => $q->tap($scope))
            ->get(['id', 'invoice_id', 'tuition_plan_id', 'line_total'])
            ->groupBy('tuition_plan_id')
            ->map(fn (Collection $items) => [
                'name' => $items->first()->tuitionPlan->localizedName(),
                'amount' => (int) $items->sum('line_total'),
                'count' => $items->count(),
            ])
            ->sortByDesc('amount')
            ->take(10)
            ->values()
            ->all();
    }
}
