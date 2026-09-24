<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search leads" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[12rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="priority" class="input sm:max-w-[10rem]">
                <option value="">All priorities</option>
                @foreach ($priorities as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\Lead::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add lead</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Lead</th>
                        <th class="px-4 py-3 font-medium">Value</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Follow-up</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($leads as $lead)
                        <tr wire:key="{{ $lead->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('leads.show', $lead) }}" class="font-medium text-brand-700">{{ $lead->name }}</a>
                                <div class="text-xs text-ink-500">{{ $lead->company ?: $lead->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $lead->estimated_value ? money($lead->estimated_value) : '—' }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$lead->status->tone()">{{ $lead->status->label() }}</x-badge></td>
                            <td class="px-4 py-3">{{ $lead->assignedUser?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-ink-500">{{ $lead->next_follow_up_at?->format('d M') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $lead)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $lead->id }}')">Edit</button>
                                @endcan
                                @can('delete', $lead)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $lead->id }}', event: 'confirmed-delete-lead', title: 'Delete lead?', message: 'The lead will be moved to the archive.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No leads yet" description="Add a lead to start the sales pipeline." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $leads->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit lead' : 'New lead' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Name</label><input type="text" wire:model="form.name" class="input">@error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Company</label><input type="text" wire:model="form.company" class="input"></div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Email</label><input type="email" wire:model="form.email" class="input"></div>
                    <div><label class="label">Phone</label><input type="text" wire:model="form.phone" class="input"></div>
                </div>
                <div><label class="label">Source</label>
                    <select wire:model="form.source" class="input">
                        <option value="">Select</option>
                        @foreach ($sources as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Requirement</label><textarea wire:model="form.requirement" rows="3" class="input"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Estimated value</label><input type="number" step="0.01" wire:model="form.estimated_value" class="input"></div>
                    <div><label class="label">Assigned to</label>
                        <select wire:model="form.assigned_user_id" class="input">
                            <option value="">Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Status</label>
                        <select wire:model="form.status" class="input">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label">Priority</label>
                        <select wire:model="form.priority" class="input">
                            @foreach ($priorities as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><label class="label">Next follow-up</label><input type="datetime-local" wire:model="form.next_follow_up_at" class="input"></div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="3" class="input"></textarea></div>
                <div><label class="label">Lost reason</label><input type="text" wire:model="form.lost_reason" class="input">@error('form.lost_reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save lead</button>
                </div>
            </form>
        </div>
    </div>
</div>
