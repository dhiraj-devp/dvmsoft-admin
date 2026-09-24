@extends('layouts.client')

@section('content')
    <x-page-header title="{{ $invoice->number }}" :description="$invoice->title" :breadcrumbs="['Invoices' => route('client.invoices.index'), $invoice->number => null]">
        <x-slot:actions>
            <a href="{{ route('client.invoices.pdf', $invoice) }}" class="btn-secondary">Download PDF</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4 text-sm"><p class="text-ink-500">Status</p><x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge></div>
        <div class="card p-4 text-sm"><p class="text-ink-500">Total</p><p class="text-lg font-semibold">{{ money($invoice->total) }}</p></div>
        <div class="card p-4 text-sm"><p class="text-ink-500">Paid</p><p class="text-lg font-semibold">{{ money($invoice->amount_paid) }}</p></div>
        <div class="card p-4 text-sm"><p class="text-ink-500">Outstanding</p><p class="text-lg font-semibold">{{ money($invoice->balance) }}</p></div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-ink-500">
                    <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-t border-ink-50 dark:border-ink-800">
                            <td class="px-5 py-3">{{ $item->description }}</td>
                            <td class="px-5 py-3">{{ money($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Payments</h2>
            @forelse ($invoice->payments as $payment)
                <div class="mt-3 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>{{ $payment->paid_on?->format(settings('company.date_format', 'd M Y')) }} · {{ $payment->method->label() }}</span>
                    <span class="font-medium">{{ money($payment->amount) }}</span>
                </div>
            @empty
                <p class="mt-3 text-ink-500">No payments recorded yet.</p>
            @endforelse
        </section>
    </div>
@endsection
