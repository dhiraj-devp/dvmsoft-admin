<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search invoices" class="input sm:max-w-xs">
        <select wire:model.live="status" class="input sm:max-w-xs">
            <option value="">All statuses</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Balance</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($invoices as $invoice)
                        <tr wire:key="{{ $invoice->id }}">
                            <td class="px-4 py-3"><a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-brand-700">{{ $invoice->number }}</a></td>
                            <td class="px-4 py-3">{{ $invoice->client?->name }}</td>
                            <td class="px-4 py-3">{{ $invoice->project?->number ?: '—' }}</td>
                            <td class="px-4 py-3">{{ money($invoice->total) }}</td>
                            <td class="px-4 py-3">{{ money($invoice->balance) }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('invoices.pdf', $invoice) }}" class="text-sm font-medium text-brand-700">PDF</a>
                                @can('delete', $invoice)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $invoice->id }}', event: 'confirmed-delete-invoice', title: 'Delete invoice?', message: 'Draft invoices can be removed.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No invoices yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
