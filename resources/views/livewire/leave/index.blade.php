<div>
    @if ($balances->isNotEmpty())
        <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($balances as $balance)
                <div class="card p-4 text-sm">
                    <p class="text-ink-500">{{ $balance->leaveType?->name }}</p>
                    <p class="mt-1 font-semibold">{{ $balance->available() }} available</p>
                    <p class="text-xs text-ink-400">Used {{ $balance->used }} · Pending {{ $balance->pending }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search leave" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[10rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\LeaveRequest::class)
            <button type="button" wire:click="create" class="btn-primary">Request leave</button>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Dates</th>
                    <th class="px-4 py-3">Days</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @forelse ($requests as $request)
                    <tr>
                        <td class="px-4 py-3">{{ $request->employee?->name() }}</td>
                        <td class="px-4 py-3">{{ $request->leaveType?->name }}</td>
                        <td class="px-4 py-3">{{ $request->start_date?->format('d M') }} – {{ $request->end_date?->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $request->days }}</td>
                        <td class="px-4 py-3"><x-badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-badge></td>
                        <td class="px-4 py-3 text-right">
                            @can('approve', $request)
                                <button type="button" class="text-sm font-medium text-brand-700" wire:click="approve('{{ $request->id }}')">Approve</button>
                            @endcan
                            @can('reject', $request)
                                <button type="button" class="ml-3 text-sm font-medium text-red-600" wire:click="reject('{{ $request->id }}')">Reject</button>
                            @endcan
                            @can('cancel', $request)
                                <button type="button" class="ml-3 text-sm font-medium text-ink-500" wire:click="cancel('{{ $request->id }}')">Cancel</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No leave requests" /></td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($requests->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $requests->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Leave request</h2>
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
                <div>
                    <label class="label">Leave type</label>
                    <select wire:model="form.leave_type_id" class="input">
                        <option value="">Select</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('form.leave_type_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Start</label><input type="date" wire:model="form.start_date" class="input"></div>
                    <div><label class="label">End</label><input type="date" wire:model="form.end_date" class="input"></div>
                </div>
                <div><label class="label">Reason</label><textarea wire:model="form.reason" rows="3" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
