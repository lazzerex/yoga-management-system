@extends('pdf.layout')

@section('content')
    <table class="meta">
        <tr>
            <td class="label">{{ __('pdf.invoiceStudent') }}</td>
            <td class="strong">{{ $invoice->studentProfile->user->name }}</td>
            <td class="label">{{ __('pdf.invoiceIssued') }}</td>
            <td>{{ $invoice->issued_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('pdf.invoiceBranch') }}</td>
            <td>{{ $invoice->branch->name }}</td>
            <td class="label">{{ __('pdf.invoiceDue') }}</td>
            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('pdf.invoiceStatus') }}</td>
            <td colspan="3">
                <span class="tag {{ $invoice->displayStatus() === 'paid' ? 'tag-paid' : 'tag-due' }}">
                    {{ __('pdf.status.'.$invoice->displayStatus()) }}
                </span>
            </td>
        </tr>
    </table>

    <h2>{{ __('pdf.invoiceItems') }}</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('pdf.invoiceDescription') }}</th>
                <th class="num">{{ __('pdf.invoiceQuantity') }}</th>
                <th class="num">{{ __('pdf.invoiceUnitPrice') }}</th>
                <th class="num">{{ __('pdf.invoiceLineTotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $money($item->unit_price) }}</td>
                    <td class="num">{{ $money($item->line_total) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="num strong">{{ __('pdf.invoiceTotal') }}</td>
                <td class="num strong">{{ $money($invoice->total_amount) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num">{{ __('pdf.invoicePaid') }}</td>
                <td class="num">{{ $money($invoice->paidAmount()) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="num strong">{{ __('pdf.invoiceBalance') }}</td>
                <td class="num strong">{{ $money($invoice->balance()) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('pdf.invoicePayments') }}</h2>
    @if ($invoice->payments->isEmpty())
        <p class="empty">{{ __('pdf.invoiceNoPayments') }}</p>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th>{{ __('pdf.paymentDate') }}</th>
                    <th>{{ __('pdf.paymentMethod') }}</th>
                    <th>{{ __('pdf.paymentReference') }}</th>
                    <th class="num">{{ __('pdf.paymentAmount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->payments->sortByDesc('paid_at') as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td>{{ __('pdf.method.'.$payment->method) }}</td>
                        <td class="muted">{{ $payment->reference ?: '—' }}</td>
                        <td class="num">
                            {{ $money($payment->amount) }}
                            @if ($payment->isVoided())
                                <span class="tag tag-void">{{ __('pdf.paymentVoided') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($invoice->note)
        <h2>{{ __('pdf.invoiceNote') }}</h2>
        <p>{{ $invoice->note }}</p>
    @endif
@endsection
