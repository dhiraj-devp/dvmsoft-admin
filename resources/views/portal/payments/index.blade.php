@extends('layouts.client')

@section('content')
    <x-page-header title="Payments" description="Payment history for your invoices." />

    <form method="GET" class="mb-4">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search by invoice or reference" class="input max-w-sm">
        <button class="btn-secondary ml-2">Search</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 text-xs uppercase text-ink-500 dark:border-ink-800">
                <tr>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3">Invoice</th>
                    <th class="px-5 py-3">Method</th>
                    <th class="px-5 py-3">Amount</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">{{ $payment->paid_on?->format(settings('company.date_format', 'd M Y')) }}</td>
                        <td class="px-5 py-3">
                            <a href="{{ route('client.invoices.show', $payment->invoice) }}" class="text-brand-700">{{ $payment->invoice?->number }}</a>
                            <p class="text-xs text-ink-500">{{ $payment->reference ?: '—' }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $payment->method->label() }}</td>
                        <td class="px-5 py-3">{{ money($payment->amount) }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('client.payments.pdf', $payment) }}" class="text-brand-700">Receipt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No payments yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
