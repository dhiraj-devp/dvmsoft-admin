<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->number }}</title>
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
    </style>
</head>
<body>
    <div class="row">
        <div class="left">
            <div class="muted">{{ company_name() }}</div>
            <h1>Quotation</h1>
            <p>{{ settings('company.address') }}</p>
            <p>{{ settings('company.email') }} · {{ settings('company.phone') }}</p>
            @if (settings('company.gst_number'))
                <p>GST: {{ settings('company.gst_number') }}</p>
            @endif
        </div>
        <div class="right">
            <p><strong>{{ $quotation->number }}</strong></p>
            <p class="badge">{{ $quotation->status->label() }}</p>
            <p>Date: {{ $quotation->created_at?->format(settings('company.date_format', 'd M Y')) }}</p>
            <p>Valid until: {{ $quotation->valid_until?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="row">
        <div class="left">
            <div class="muted">Bill to</div>
            <strong>{{ $quotation->client->name }}</strong>
            <p>{{ $quotation->client->address }}</p>
            <p>{{ $quotation->client->email }} · {{ $quotation->client->phone }}</p>
            @if ($quotation->client->gst_number)
                <p>GST: {{ $quotation->client->gst_number }}</p>
            @endif
        </div>
        <div class="clear"></div>
    </div>

    <p><strong>{{ $quotation->title }}</strong></p>

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
            @foreach ($quotation->items as $item)
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
        <p>Subtotal: {{ money($quotation->subtotal) }}</p>
        <p>Discount: {{ money($quotation->discount_amount) }}</p>
        <p>Tax / GST: {{ money($quotation->tax_amount) }}</p>
        <p><strong>Total: {{ money($quotation->total) }}</strong></p>
    </div>

    <p>Payment terms: {{ config('crm.payment_terms.'.$quotation->payment_terms, $quotation->payment_terms) }}</p>
    @if ($quotation->notes)
        <p>{{ $quotation->notes }}</p>
    @endif
</body>
</html>
