@extends('layouts.client')

@section('content')
    <x-page-header title="Quotations" description="Proposals sent to your account." />

    <form method="GET" class="mb-4">
        <select name="status" class="input max-w-xs">
            <option value="">All statuses</option>
            @foreach (\App\Enums\QuotationStatus::cases() as $item)
                @if ($item !== \App\Enums\QuotationStatus::Draft)
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
                    <th class="px-5 py-3">Quotation</th>
                    <th class="px-5 py-3">Valid until</th>
                    <th class="px-5 py-3">Total</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotations as $quotation)
                    <tr class="border-b border-ink-50 dark:border-ink-800">
                        <td class="px-5 py-3">
                            <a href="{{ route('client.quotations.show', $quotation) }}" class="font-medium text-brand-700">{{ $quotation->number }}</a>
                            <p class="text-xs text-ink-500">{{ $quotation->title }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $quotation->valid_until?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</td>
                        <td class="px-5 py-3">{{ money($quotation->total) }}</td>
                        <td class="px-5 py-3"><x-badge :tone="$quotation->status->tone()">{{ $quotation->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state title="No quotations" description="Sent quotations will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $quotations->links() }}</div>
@endsection
