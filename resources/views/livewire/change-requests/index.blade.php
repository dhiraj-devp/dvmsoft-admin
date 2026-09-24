<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search change requests" class="input sm:max-w-xs">
        @can('create', App\Models\ChangeRequest::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New request</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Request</th>
                        <th class="px-4 py-3 font-medium">Impact</th>
                        <th class="px-4 py-3 font-medium">Requested by</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($changeRequests as $changeRequest)
                        <tr wire:key="{{ $changeRequest->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $changeRequest->number }}</div>
                                <div class="text-xs text-ink-500">{{ $changeRequest->title }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div>{{ $changeRequest->impact_on_cost !== null ? money($changeRequest->impact_on_cost) : 'No cost impact' }}</div>
                                <div class="text-ink-500">{{ $changeRequest->impact_on_timeline_days ? $changeRequest->impact_on_timeline_days.' days' : 'No timeline impact' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $changeRequest->requesterName() }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$changeRequest->status->tone()">{{ $changeRequest->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('approve', $changeRequest)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="approve('{{ $changeRequest->id }}')">Approve</button>
                                    <button type="button" class="ml-2 text-sm font-medium text-red-600" wire:click="reject('{{ $changeRequest->id }}')">Reject</button>
                                @endcan
                                @can('implement', $changeRequest)
                                    <button type="button" class="ml-2 text-sm font-medium text-brand-700" wire:click="implement('{{ $changeRequest->id }}')">Implement</button>
                                @endcan
                                @can('update', $changeRequest)
                                    <button type="button" class="ml-2 text-sm font-medium text-brand-700" wire:click="edit('{{ $changeRequest->id }}')">Edit</button>
                                @endcan
                                @can('delete', $changeRequest)
                                    <button type="button" class="ml-2 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $changeRequest->id }}', event: 'confirmed-delete-change-request', title: 'Delete change request?', message: 'This request will be archived.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No change requests" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($changeRequests->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $changeRequests->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit change request' : 'New change request' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div><label class="label">Title</label><input type="text" wire:model="form.title" class="input">@error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="form.description" rows="4" class="input"></textarea></div>
                <div>
                    <label class="label">Requested by</label>
                    <select wire:model="form.requested_by_id" class="input">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Cost impact</label><input type="number" step="0.01" wire:model="form.impact_on_cost" class="input"></div>
                    <div><label class="label">Timeline (days)</label><input type="number" wire:model="form.impact_on_timeline_days" class="input"></div>
                </div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="2" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save request</button>
                </div>
            </form>
        </div>
    </div>
</div>
