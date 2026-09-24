<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search employees" class="input sm:max-w-xs">
        <select wire:model.live="status" class="input sm:max-w-xs">
            <option value="">All statuses</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="departmentId" class="input sm:max-w-xs">
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Employee</th>
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Department</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($employees as $employee)
                        <tr wire:key="{{ $employee->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('employees.show', $employee) }}" class="font-medium text-brand-700">{{ $employee->name() }}</a>
                                <div class="text-xs text-ink-500">{{ $employee->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $employee->code() ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->user?->department?->name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->employment_type->label() }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$employee->employment_status->tone()">{{ $employee->employment_status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $employee)
                                    <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $employee->id }}', event: 'confirmed-delete-employee', title: 'Remove employee?', message: 'The login account will be deactivated. The HR profile will be removed from the directory.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No employees yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $employees->links() }}</div>
        @endif
    </div>
</div>
