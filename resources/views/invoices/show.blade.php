@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $invoice->number }}" description="{{ $invoice->title }}" :breadcrumbs="['Finance' => route('finance.dashboard'), 'Invoices' => route('invoices.index'), $invoice->number => null]">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary">Download PDF</a>
                @can('update', $invoice)
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn-secondary">Edit</a>
                @endcan
                @can('send', $invoice)
                    <form method="POST" action="{{ route('invoices.send', $invoice) }}">@csrf<button class="btn-primary">Mark sent</button></form>
                @endcan
                @can('cancel', $invoice)
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}">@csrf<button class="btn-danger">Cancel</button></form>
                @endcan
                @can('create', App\Models\Payment::class)
                    @if ($invoice->status->acceptsPayments())
                        <a href="{{ route('payments.index', ['invoice_id' => $invoice->id]) }}" class="btn-primary">Record payment</a>
                    @endif
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <div>
                    <p class="text-sm text-ink-500">Client</p>
                    <a href="{{ route('clients.show', $invoice->client) }}" class="font-semibold text-brand-700">{{ $invoice->client->name }}</a>
                    @if ($invoice->project)
                        <p class="mt-1 text-sm text-ink-500">Project: <a class="text-brand-700" href="{{ route('projects.show', $invoice->project) }}">{{ $invoice->project->number }}</a></p>
                    @endif
                    @if ($invoice->quotation)
                        <p class="text-sm text-ink-500">Quotation: <a class="text-brand-700" href="{{ route('quotations.show', $invoice->quotation) }}">{{ $invoice->quotation->number }}</a></p>
                    @endif
                </div>
                <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                        <tr>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Disc %</th>
                            <th class="px-4 py-3">Tax %</th>
                            <th class="px-4 py-3 text-right">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-3">{{ $item->description }}</td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">{{ money($item->unit_price) }}</td>
                                <td class="px-4 py-3">{{ $item->discount_percent }}%</td>
                                <td class="px-4 py-3">{{ $item->tax_percent }}%</td>
                                <td class="px-4 py-3 text-right font-medium">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="space-y-2 border-t border-ink-100 px-5 py-4 text-sm dark:border-ink-800">
                <div class="flex justify-between"><span class="text-ink-500">Subtotal</span><span>{{ money($invoice->subtotal) }}</span></div>
                <div class="flex justify-between"><span class="text-ink-500">Discount</span><span>- {{ money($invoice->discount_amount) }}</span></div>
                <div class="flex justify-between"><span class="text-ink-500">Tax / GST</span><span>{{ money($invoice->tax_amount) }}</span></div>
                <div class="flex justify-between text-base font-semibold"><span>Total</span><span>{{ money($invoice->total) }}</span></div>
                <div class="flex justify-between"><span class="text-ink-500">Paid</span><span>{{ money($invoice->amount_paid) }}</span></div>
                <div class="flex justify-between font-semibold"><span>Balance</span><span>{{ money($invoice->balance) }}</span></div>
            </div>
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Details</h2>
            <dl class="mt-4 space-y-3">
                <div>
                    <dt class="text-ink-500">Invoice date</dt>
                    <dd class="font-medium">{{ $invoice->invoice_date?->format(settings('company.date_format', 'd M Y')) }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Due date</dt>
                    <dd class="font-medium">{{ $invoice->due_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Payment terms</dt>
                    <dd class="font-medium">{{ config('crm.payment_terms.'.$invoice->payment_terms, $invoice->payment_terms ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-500">Prepared by</dt>
                    <dd class="font-medium">{{ $invoice->createdBy?->name ?: '—' }}</dd>
                </div>
            </dl>
            @if ($invoice->notes)
                <p class="mt-4 rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $invoice->notes }}</p>
            @endif
        </section>
    </div>

    <section class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
            <h2 class="font-semibold">Payments</h2>
        </div>
        @forelse ($invoice->payments as $payment)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <span>
                    <span class="font-medium">{{ money($payment->amount) }}</span>
                    <span class="text-ink-500"> · {{ $payment->paid_on?->format(settings('company.date_format', 'd M Y')) }} · {{ $payment->method->label() }}</span>
                </span>
                <a href="{{ route('payments.pdf', $payment) }}" class="text-brand-700">Receipt PDF</a>
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-ink-500">No payments recorded yet.</p>
        @endforelse
        @if ($invoice->status->acceptsPayments())
            @can('create', App\Models\Payment::class)
                <div class="border-t border-ink-100 px-5 py-4 dark:border-ink-800">
                    <a href="{{ route('payments.index', ['invoice_id' => $invoice->id]) }}" class="btn-primary">Record payment</a>
                </div>
            @endcan
        @endif
    </section>
@endsection
