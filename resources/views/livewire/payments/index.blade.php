<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search payments" class="input sm:max-w-xs">
        @can('create', App\Models\Payment::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Record payment</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Method</th>
                        <th class="px-4 py-3 font-medium">Reference</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($payments as $payment)
                        <tr wire:key="{{ $payment->id }}">
                            <td class="px-4 py-3">{{ $payment->paid_on?->format(settings('company.date_format', 'd M Y')) }}</td>
                            <td class="px-4 py-3"><a href="{{ route('invoices.show', $payment->invoice) }}" class="font-medium text-brand-700">{{ $payment->invoice?->number }}</a></td>
                            <td class="px-4 py-3">{{ $payment->client?->name }}</td>
                            <td class="px-4 py-3">{{ money($payment->amount) }}</td>
                            <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                            <td class="px-4 py-3">{{ $payment->reference ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('payments.pdf', $payment) }}" class="text-sm font-medium text-brand-700">Receipt</a>
                                @if ($payment->receipt_path)
                                    <a href="{{ route('payments.receipt', $payment) }}" class="ml-3 text-sm font-medium text-brand-700">File</a>
                                @endif
                                @can('update', $payment)
                                    <button type="button" class="ml-3 text-sm font-medium text-brand-700" wire:click="edit('{{ $payment->id }}')">Edit</button>
                                @endcan
                                @can('delete', $payment)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $payment->id }}', event: 'confirmed-delete-payment', title: 'Delete payment?', message: 'The invoice balance will be recalculated.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No payments yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $payments->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit payment' : 'Record payment' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Invoice</label>
                    <select wire:model="form.invoice_id" class="input" @if($lockedInvoiceId) disabled @endif>
                        <option value="">Select invoice</option>
                        @foreach ($invoiceOptions as $invoice)
                            <option value="{{ $invoice->id }}">{{ $invoice->number }} · {{ $invoice->client?->name }} · due {{ money($invoice->balance) }}</option>
                        @endforeach
                    </select>
                    @error('form.invoice_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Amount</label>
                        <input type="number" step="0.01" wire:model="form.amount" class="input">
                        @error('form.amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Payment date</label>
                        <input type="date" wire:model="form.paid_on" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Method</label>
                    <select wire:model="form.method" class="input">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Transaction / reference</label>
                    <input type="text" wire:model="form.reference" class="input">
                </div>
                <div>
                    <label class="label">Notes</label>
                    <textarea wire:model="form.notes" rows="3" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Receipt (optional)</label>
                    <input type="file" wire:model="receipt" class="input">
                    @error('receipt')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
