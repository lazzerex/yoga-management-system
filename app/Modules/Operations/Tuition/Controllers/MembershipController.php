<?php

namespace App\Modules\Operations\Tuition\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Response;

class MembershipController extends Controller
{
    public function show(Request $request): Response
    {
        $studentProfileId = $request->user()->studentProfile?->id;

        $invoices = Invoice::with('branch:id,name')
            ->withSum('recordedPayments', 'amount')
            ->when($studentProfileId, fn ($q) => $q->where('student_profile_id', $studentProfileId), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        return inertia('Member/MyMembership', [
            'entitlements' => $this->entitlements($studentProfileId),
            'invoices' => $invoices->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'branch_name' => $invoice->branch->name,
                'issued_at' => $invoice->issued_at->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'status' => $invoice->displayStatus(),
                'total_amount' => $invoice->total_amount,
                'balance' => $invoice->balance(),
            ]),
            'outstanding' => (int) $invoices->filter(fn (Invoice $invoice) => in_array($invoice->status, Invoice::OPEN_STATUSES, true))
                ->sum(fn (Invoice $invoice) => $invoice->balance()),
        ]);
    }

    /** Only paid lines grant access, and an undated line (a one-off charge) grants nothing ongoing. */
    private function entitlements(?int $studentProfileId): array
    {
        if (! $studentProfileId) {
            return [];
        }

        return InvoiceItem::whereHas('invoice', fn ($q) => $q
            ->where('student_profile_id', $studentProfileId)
            ->where('status', 'paid'))
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', today())
            ->orderBy('valid_until')
            ->get()
            ->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'valid_from' => $item->valid_from?->toDateString(),
                'valid_until' => $item->valid_until->toDateString(),
                'sessions_granted' => $item->sessions_granted,
            ])
            ->all();
    }
}
