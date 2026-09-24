<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search notes" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[10rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="type" class="input sm:max-w-[10rem]">
                <option value="">All types</option>
                @foreach ($types as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\FollowUp::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add follow-up</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Subject</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($followUps as $followUp)
                        <tr wire:key="{{ $followUp->id }}">
                            <td class="px-4 py-3">
                                {{ $followUp->scheduled_at?->format('d M Y H:i') }}
                                @if ($followUp->isOverdue())
                                    <div class="text-xs text-red-600">Overdue</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $followUp->subjectName() }}</div>
                                <div class="text-xs text-ink-500">{{ \Illuminate\Support\Str::limit($followUp->notes, 60) }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $followUp->type->label() }}</td>
                            <td class="px-4 py-3">{{ $followUp->assignedUser?->name ?: '—' }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$followUp->status->tone()">{{ $followUp->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('complete', $followUp)
                                    @if ($followUp->status === \App\Enums\FollowUpStatus::Pending)
                                        <button type="button" class="text-sm font-medium text-brand-700" wire:click="complete('{{ $followUp->id }}')">Done</button>
                                    @endif
                                @endcan
                                @can('update', $followUp)
                                    <button type="button" class="ml-3 text-sm font-medium text-brand-700" wire:click="edit('{{ $followUp->id }}')">Edit</button>
                                @endcan
                                @can('delete', $followUp)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $followUp->id }}', event: 'confirmed-delete-follow-up', title: 'Delete follow-up?', message: 'This reminder will be removed.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No follow-ups" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($followUps->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $followUps->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit follow-up' : 'New follow-up' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                @unless ($lockedToSubject)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Related to</label>
                            <select wire:model.live="form.followable_type" class="input">
                                <option value="{{ App\Models\Lead::class }}">Lead</option>
                                <option value="{{ App\Models\Client::class }}">Client</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Record</label>
                            <select wire:model="form.followable_id" class="input">
                                <option value="">Select</option>
                                @if ($form['followable_type'] === App\Models\Lead::class)
                                    @foreach ($leads as $lead)
                                        <option value="{{ $lead->id }}">{{ $lead->name }}{{ $lead->company ? ' · '.$lead->company : '' }}</option>
                                    @endforeach
                                @else
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('form.followable_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endunless
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Type</label>
                        <select wire:model="form.type" class="input">
                            @foreach ($types as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label">Assigned to</label>
                        <select wire:model="form.assigned_user_id" class="input">
                            <option value="">Unassigned</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><label class="label">Date & time</label><input type="datetime-local" wire:model="form.scheduled_at" class="input">@error('form.scheduled_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Reminder</label><input type="datetime-local" wire:model="form.reminder_at" class="input"></div>
                <div><label class="label">Status</label>
                    <select wire:model="form.status" class="input">
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="3" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>
