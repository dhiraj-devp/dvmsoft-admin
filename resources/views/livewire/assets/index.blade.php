<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search assets" class="input sm:max-w-xs">
        @can('create', App\Models\EmployeeAsset::class)
            <button type="button" wire:click="create" class="btn-primary">Assign asset</button>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3">Asset</th>
                    <th class="px-4 py-3">Serial</th>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Assigned</th>
                    <th class="px-4 py-3">Returned</th>
                    <th class="px-4 py-3">Condition</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @forelse ($assets as $asset)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $asset->name }}</td>
                        <td class="px-4 py-3">{{ $asset->serial_number ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $asset->employee?->name() }}</td>
                        <td class="px-4 py-3">{{ $asset->assigned_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $asset->return_date?->format('d M Y') ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $asset->condition->label() }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('return', $asset)
                                <button type="button" class="text-sm font-medium text-brand-700" wire:click="markReturned('{{ $asset->id }}')">Return</button>
                            @endcan
                            @can('delete', $asset)
                                <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $asset->id }}', event: 'confirmed-delete-asset', title: 'Delete asset?', message: 'This assignment will be removed.' })">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="No assets assigned" /></td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($assets->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $assets->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Assign asset</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Employee</label>
                    <select wire:model="form.employee_id" class="input" @if($employeeId) disabled @endif>
                        <option value="">Select</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name() }}</option>
                        @endforeach
                    </select>
                    @error('form.employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="label">Asset</label><input type="text" wire:model="form.name" class="input">@error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Serial number</label><input type="text" wire:model="form.serial_number" class="input"></div>
                <div><label class="label">Assigned date</label><input type="date" wire:model="form.assigned_date" class="input"></div>
                <div>
                    <label class="label">Condition</label>
                    <select wire:model="form.condition" class="input">
                        @foreach ($conditions as $condition)
                            <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="3" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
