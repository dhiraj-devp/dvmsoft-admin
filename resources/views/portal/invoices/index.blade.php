@extends('layouts.client')

@section('content')
    <x-page-header title="Invoices" description="Billed invoices and outstanding balances." />

    <form method="GET" class="mb-4">
        <select name="status" class="input max-w-xs">
            <option value="">All statuses</option>
            @foreach (\App\Enums\InvoiceStatus::cases() as $item)
                @if (! in_array($item, [\App\Enums\InvoiceStatus::Draft, \App\Enums\InvoiceStatus::Cancelled], true))
                    <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                @endif
            @endforeach
        </select>
        <button class="btn-secondary ml-2">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-ink-100 text-xs uppercase text-ink-500 dark:border-ink-800">
                <tr>
                    <th class="px-5 py-3">Invoice</th>
                    <th class="px-5 py-3">Due</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Balance</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">
                            <a href="{{ route('client.invoices.show', $invoice) }}" class="font-medium text-brand-700">{{ $invoice->number }}</a>
                            <p class="text-xs text-ink-500">{{ $invoice->title }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $invoice->due_date?->format(settings('company.date_format', 'd M Y')) }}</td>
                        <td class="px-5 py-3">{{ money($invoice->total) }}</td>
                        <td class="px-5 py-3">{{ money($invoice->balance) }}</td>
                        <td class="px-5 py-3"><x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No invoices" description="Issued invoices will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
