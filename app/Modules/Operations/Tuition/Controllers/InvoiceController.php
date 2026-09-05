<?php

namespace App\Modules\Operations\Tuition\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use App\Modules\Operations\Tuition\Actions\CreateInvoiceAction;
use App\Modules\Operations\Tuition\Actions\DeleteInvoiceAction;
use App\Modules\Operations\Tuition\Actions\RecordPaymentAction;
use App\Modules\Operations\Tuition\Actions\TuitionStatsAction;
use App\Modules\Operations\Tuition\Actions\VoidPaymentAction;
use App\Modules\Operations\Tuition\Actions\WaiveInvoiceAction;
use App\Modules\Operations\Tuition\Requests\StoreInvoiceRequest;
use App\Modules\Operations\Tuition\Requests\StorePaymentRequest;
use App\Modules\Operations\Tuition\Requests\VoidPaymentRequest;
use App\Support\Table\SortsQueries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use SortsQueries;

    public function index(Request $request, TuitionStatsAction $stats): Response
    {
        $scope = $this->branchScope($request);
        $canManage = $request->user()->can('operations.tuition.manage');

        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $overdue = $request->boolean('overdue');
        $from = $request->date('from')?->toDateString();
        $to = $request->date('to')?->toDateString();

        $query = Invoice::with(['studentProfile.user:id,name', 'branch:id,name'])
            ->withSum('recordedPayments', 'amount')
            ->tap($scope)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('studentProfile.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
            ->when(in_array($status, Invoice::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($overdue, fn ($q) => $q->overdue())
            ->when($from, fn ($q) => $q->where('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('due_date', '<=', $to));

        $sort = $this->applySort($query, $request, [
            'invoice_number' => 'invoice_number',
            'due_date' => 'due_date',
            'total_amount' => 'total_amount',
            'status' => ['unpaid', 'partial', 'paid', 'waived'],
        ], 'id');

        $invoices = $query->paginate(20)->withQueryString();

        return inertia('Operations/TuitionFees', [
            'invoices' => $invoices->through(fn (Invoice $invoice) => $this->row($invoice)),
            'stats' => $stats->execute($request->attributes->get('currentBranch')?->id),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'overdue' => $overdue ? '1' : '',
                'from' => $from ?? '',
                'to' => $to ?? '',
            ] + $sort,
            'options' => ['statuses' => Invoice::STATUSES],
            'endpoints' => [
                'create' => $canManage ? route('operations.invoices.create') : null,
                'plans' => $canManage ? route('operations.tuition-plans.index') : null,
                'export' => route('operations.invoices.export'),
                'index' => route('operations.tuition-fees'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return inertia('Operations/Tuition/Invoices/Create', [
            'options' => [
                'students' => StudentProfile::active()->with('user:id,name')->get()
                    ->map(fn (StudentProfile $profile) => ['id' => $profile->id, 'name' => $profile->user->name])
                    ->sortBy('name')->values(),
                'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
                'plans' => TuitionPlan::active()->orderBy('name')->get(['id', 'name', 'price_amount', 'type']),
            ],
            'selectedBranchId' => $request->attributes->get('currentBranch')?->id,
            'endpoints' => [
                'store' => route('operations.invoices.store'),
                'index' => route('operations.tuition-fees'),
            ],
        ]);
    }

    public function store(StoreInvoiceRequest $request, CreateInvoiceAction $action): RedirectResponse
    {
        $invoice = $action->execute($request->validated());

        return redirect()
            ->route('operations.invoices.show', $invoice)
            ->with('success', ['key' => 'flash.invoiceCreated', 'params' => ['number' => $invoice->invoice_number]]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $invoice->load([
            'studentProfile.user:id,name',
            'branch:id,name',
            'items',
            'payments.recordedBy:id,name',
            'payments.voidedBy:id,name',
            'payments.media',
        ]);

        $canManage = $request->user()->can('operations.tuition.manage');
        $settled = in_array($invoice->status, ['paid', 'waived'], true);

        return inertia('Operations/Tuition/Invoices/Show', [
            'invoice' => $this->row($invoice) + [
                'note' => $invoice->note,
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'valid_until' => $item->valid_until?->toDateString(),
                    'sessions_granted' => $item->sessions_granted,
                ]),
                'payments' => $invoice->payments->sortByDesc('paid_at')->values()->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'method' => $payment->method,
                    'paid_at' => $payment->paid_at->toIso8601String(),
                    'reference' => $payment->reference,
                    'recorded_by' => $payment->recordedBy?->name,
                    'voided_at' => $payment->voided_at?->toIso8601String(),
                    'voided_by' => $payment->voidedBy?->name,
                    'void_reason' => $payment->void_reason,
                    'proofUrl' => $payment->getFirstMedia('proof')
                        ? route('operations.files.show', $payment->getFirstMedia('proof'))
                        : null,
                    'voidUrl' => $canManage && ! $payment->isVoided()
                        ? route('operations.invoices.payments.void', [$invoice, $payment])
                        : null,
                ]),
            ],
            'methods' => Payment::METHODS,
            'endpoints' => [
                'payment' => $canManage && ! $settled ? route('operations.invoices.payments.store', $invoice) : null,
                'waive' => $canManage && $invoice->status === 'unpaid' ? route('operations.invoices.waive', $invoice) : null,
                'destroy' => $canManage && $invoice->payments->isEmpty() ? route('operations.invoices.destroy', $invoice) : null,
                'index' => route('operations.tuition-fees'),
            ],
        ]);
    }

    public function recordPayment(StorePaymentRequest $request, Invoice $invoice, RecordPaymentAction $action): RedirectResponse
    {
        $action->execute($invoice, $request->validated(), $request->user());

        return back()->with('success', ['key' => 'flash.paymentRecorded']);
    }

    public function voidPayment(VoidPaymentRequest $request, Invoice $invoice, Payment $payment, VoidPaymentAction $action): RedirectResponse
    {
        $action->execute($payment, $request->validated(), $request->user());

        return back()->with('success', ['key' => 'flash.paymentVoided']);
    }

    public function waive(Invoice $invoice, WaiveInvoiceAction $action): RedirectResponse
    {
        $action->execute($invoice);

        return back()->with('success', ['key' => 'flash.invoiceWaived']);
    }

    public function destroy(Invoice $invoice, DeleteInvoiceAction $action): RedirectResponse
    {
        $number = $invoice->invoice_number;
        $action->execute($invoice);

        return redirect()
            ->route('operations.tuition-fees')
            ->with('success', ['key' => 'flash.invoiceDeleted', 'params' => ['number' => $number]]);
    }

    public function export(Request $request): StreamedResponse
    {
        $scope = $this->branchScope($request);
        $filename = 'invoices-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($scope) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice', 'Student', 'Branch', 'Issued', 'Due', 'Status', 'Total', 'Paid', 'Balance']);

            Invoice::with(['studentProfile.user:id,name', 'branch:id,name'])
                ->withSum('recordedPayments', 'amount')
                ->tap($scope)
                ->orderBy('id')
                ->chunk(200, function ($invoices) use ($handle) {
                    foreach ($invoices as $invoice) {
                        fputcsv($handle, [
                            $invoice->invoice_number,
                            $invoice->studentProfile->user->name,
                            $invoice->branch->name,
                            $invoice->issued_at->toDateString(),
                            $invoice->due_date->toDateString(),
                            $invoice->displayStatus(),
                            $invoice->total_amount,
                            $invoice->paidAmount(),
                            $invoice->balance(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function branchScope(Request $request): callable
    {
        $branchId = $request->attributes->get('currentBranch')?->id;

        return fn ($query) => $query->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    private function row(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'student_name' => $invoice->studentProfile->user->name,
            'branch_name' => $invoice->branch->name,
            'issued_at' => $invoice->issued_at->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'status' => $invoice->displayStatus(),
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->paidAmount(),
            'balance' => $invoice->balance(),
            'showUrl' => route('operations.invoices.show', $invoice->id),
        ];
    }
}
