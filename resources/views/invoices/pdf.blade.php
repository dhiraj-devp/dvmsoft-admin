<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { font-size: 22px; margin: 0; }
        .muted { color: #64748b; }
        .row { width: 100%; margin-bottom: 24px; }
        .left { float: left; width: 55%; }
        .right { float: right; width: 40%; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #f1f5f9; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
        .totals { width: 280px; margin-left: auto; }
        .clear { clear: both; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #ccfbf1; color: #115e59; }
        .pay { margin-top: 28px; padding: 12px; background: #f8fafc; }
        img.logo { height: 42px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="row">
        <div class="left">
            @if (! empty($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="">
            @endif
            <div class="muted">{{ company_name() }}</div>
            <h1>Tax Invoice</h1>
            <p>{{ settings('company.address') }}</p>
            <p>{{ settings('company.email') }} · {{ settings('company.phone') }}</p>
            @if (settings('company.gst_number'))
                <p>GST: {{ settings('company.gst_number') }}</p>
            @endif
            @if (settings('company.pan_number'))
                <p>PAN: {{ settings('company.pan_number') }}</p>
            @endif
        </div>
        <div class="right">
            <p><strong>{{ $invoice->number }}</strong></p>
            <p class="badge">{{ $invoice->status->label() }}</p>
            <p>Date: {{ $invoice->invoice_date?->format(settings('company.date_format', 'd M Y')) }}</p>
            <p>Due: {{ $invoice->due_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="row">
        <div class="left">
            <div class="muted">Bill to</div>
            <strong>{{ $invoice->client->name }}</strong>
            <p>{{ $invoice->client->address }}</p>
            <p>{{ $invoice->client->email }} · {{ $invoice->client->phone }}</p>
            @if ($invoice->client->gst_number)
                <p>GST: {{ $invoice->client->gst_number }}</p>
            @endif
        </div>
        <div class="clear"></div>
    </div>

    <p><strong>{{ $invoice->title }}</strong></p>
    @if ($invoice->project)
        <p class="muted">Project {{ $invoice->project->number }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Disc %</th>
                <th>Tax %</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ money($item->unit_price) }}</td>
                    <td>{{ $item->discount_percent }}</td>
                    <td>{{ $item->tax_percent }}</td>
                    <td>{{ money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Subtotal: {{ money($invoice->subtotal) }}</p>
        <p>Discount: {{ money($invoice->discount_amount) }}</p>
        <p>Tax / GST: {{ money($invoice->tax_amount) }}</p>
        <p><strong>Total: {{ money($invoice->total) }}</strong></p>
        <p>Paid: {{ money($invoice->amount_paid) }}</p>
        <p><strong>Balance: {{ money($invoice->balance) }}</strong></p>
    </div>

    <p>Payment terms: {{ config('crm.payment_terms.'.$invoice->payment_terms, $invoice->payment_terms) }}</p>
    @if ($invoice->notes)
        <p>{{ $invoice->notes }}</p>
    @endif

    <div class="pay">
        <strong>Payment information</strong>
        @if (settings('finance.bank_name'))
            <p>Bank: {{ settings('finance.bank_name') }}</p>
        @endif
        @if (settings('finance.bank_account_name'))
            <p>Account name: {{ settings('finance.bank_account_name') }}</p>
        @endif
        @if (settings('finance.bank_account_number'))
            <p>Account number: {{ settings('finance.bank_account_number') }}</p>
        @endif
        @if (settings('finance.bank_ifsc'))
            <p>IFSC: {{ settings('finance.bank_ifsc') }}</p>
        @endif
        @if (settings('finance.upi_id'))
            <p>UPI: {{ settings('finance.upi_id') }}</p>
        @endif
        @if (settings('finance.payment_instructions'))
            <p>{{ settings('finance.payment_instructions') }}</p>
        @endif
        @if (! settings('finance.bank_name') && ! settings('finance.upi_id') && ! settings('finance.payment_instructions'))
            <p class="muted">Add bank and UPI details in Settings → Finance.</p>
        @endif
    </div>
</body>
</html>
