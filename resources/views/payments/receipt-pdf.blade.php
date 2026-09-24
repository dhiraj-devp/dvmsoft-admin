<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { font-size: 22px; margin: 0; }
        .muted { color: #64748b; }
        .row { width: 100%; margin-bottom: 24px; }
        .left { float: left; width: 55%; }
        .right { float: right; width: 40%; text-align: right; }
        .clear { clear: both; }
        .box { margin-top: 16px; padding: 16px; background: #f8fafc; }
        .amount { font-size: 20px; font-weight: bold; }
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
            <h1>Payment receipt</h1>
            <p>{{ settings('company.address') }}</p>
            <p>{{ settings('company.email') }} · {{ settings('company.phone') }}</p>
            @if (settings('company.gst_number'))
                <p>GST: {{ settings('company.gst_number') }}</p>
            @endif
        </div>
        <div class="right">
            <p><strong>{{ $payment->invoice->number }}</strong></p>
            <p>Receipt date: {{ $payment->paid_on?->format(settings('company.date_format', 'd M Y')) }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <p>Received from <strong>{{ $payment->client->name }}</strong></p>
    <div class="box">
        <p class="muted">Amount received</p>
        <p class="amount">{{ money($payment->amount) }}</p>
        <p>Method: {{ $payment->method->label() }}</p>
        @if ($payment->reference)
            <p>Reference: {{ $payment->reference }}</p>
        @endif
        <p>Invoice total: {{ money($payment->invoice->total) }}</p>
        <p>Invoice balance after this receipt: {{ money($payment->invoice->balance) }}</p>
    </div>

    @if ($payment->notes)
        <p>{{ $payment->notes }}</p>
    @endif

    <p class="muted">This receipt was generated from {{ company_name() }} Admin OS. Currency: {{ settings('company.currency', 'INR') }}.</p>
</body>
</html>
