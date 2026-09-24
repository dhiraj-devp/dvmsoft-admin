@extends('layouts.app')

@section('content')
    <x-page-header title="Finance overview" description="Revenue, collections, and spend for {{ company_name() }}." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Overview' => null]">
        <x-slot:actions>
            @can('ai.finance.use')
                <a href="{{ route('ai.finance') }}" class="btn-secondary">AI finance insights</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-ink-500">{{ $metric['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 p-2.5 text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                        <x-icon :name="$metric['icon']" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink-400">{{ $metric['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent invoices</h2></div>
            @forelse ($recentInvoices as $invoice)
                <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $invoice->number }}</span>
                        <span class="text-xs text-ink-500">{{ $invoice->client?->name }}</span>
                    </span>
                    <span class="text-xs">{{ money($invoice->total) }}</span>
                </a>
            @empty
                <x-empty-state title="No invoices yet" />
            @endforelse
        </section>
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent payments</h2></div>
            @forelse ($recentPayments as $payment)
                <a href="{{ route('payments.pdf', $payment) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ money($payment->amount) }}</span>
                        <span class="text-xs text-ink-500">{{ $payment->invoice?->number }} · {{ $payment->client?->name }}</span>
                    </span>
                    <span class="text-xs">{{ $payment->paid_on?->format('d M') }}</span>
                </a>
            @empty
                <x-empty-state title="No payments yet" />
            @endforelse
        </section>
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Overdue invoices</h2></div>
            @forelse ($overdueInvoices as $invoice)
                <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $invoice->number }}</span>
                        <span class="text-xs text-ink-500">{{ $invoice->client?->name }} · {{ $invoice->daysOverdue() }} days</span>
                    </span>
                    <span class="text-xs">{{ money($invoice->balance) }}</span>
                </a>
            @empty
                <x-empty-state title="Nothing overdue" />
            @endforelse
        </section>
    </div>
@endsection
