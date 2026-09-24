<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search expenses" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[10rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\Expense::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add expense</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Vendor</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($expenses as $expense)
                        <tr wire:key="{{ $expense->id }}">
                            <td class="px-4 py-3 font-medium">{{ $expense->number }}</td>
                            <td class="px-4 py-3">{{ $expense->expense_date?->format(settings('company.date_format', 'd M Y')) }}</td>
                            <td class="px-4 py-3">{{ $expense->vendor }}</td>
                            <td class="px-4 py-3">{{ $expense->categoryLabel() }}</td>
                            <td class="px-4 py-3">{{ money($expense->total()) }}</td>
                            <td class="px-4 py-3">
                                @if ($expense->project)
                                    <a href="{{ route('projects.show', $expense->project) }}" class="text-brand-700">{{ $expense->project->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-badge :tone="$expense->status->tone()">{{ $expense->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @if ($expense->receipt_path)
                                    <a href="{{ route('expenses.receipt', $expense) }}" class="text-sm font-medium text-brand-700">Receipt</a>
                                @endif
                                @can('update', $expense)
                                    <button type="button" class="ml-3 text-sm font-medium text-brand-700" wire:click="edit('{{ $expense->id }}')">Edit</button>
                                @endcan
                                @can('delete', $expense)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $expense->id }}', event: 'confirmed-delete-expense', title: 'Delete expense?', message: 'This expense will be removed.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No expenses yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $expenses->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit expense' : 'New expense' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Date</label>
                        <input type="date" wire:model="form.expense_date" class="input">
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="form.status" class="input">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Category</label>
                    <select wire:model="form.category" class="input">
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Vendor / payee</label>
                    <input type="text" wire:model="form.vendor" class="input">
                    @error('form.vendor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Amount</label>
                        <input type="number" step="0.01" wire:model="form.amount" class="input">
                        @error('form.amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Tax</label>
                        <input type="number" step="0.01" wire:model="form.tax_amount" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Payment method</label>
                    <select wire:model="form.payment_method" class="input">
                        @foreach ($methods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Project (optional)</label>
                    <select wire:model="form.project_id" class="input">
                        <option value="">None</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->number }} · {{ $project->name }}</option>
                        @endforeach
                    </select>
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
                    <button type="submit" class="btn-primary">Save expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
