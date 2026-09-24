<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search tickets" class="input sm:max-w-xs">
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
        <select wire:model.live="mine" class="input sm:max-w-[12rem]">
            <option value="">All assignees</option>
            <option value="1">Assigned to me</option>
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Ticket</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Priority</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">SLA</th>
                        <th class="px-4 py-3 font-medium">Assignee</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($tickets as $ticket)
                        <tr wire:key="{{ $ticket->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-brand-700">{{ $ticket->number }}</a>
                                <div class="text-xs text-ink-500">{{ $ticket->subject }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $ticket->client?->name }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-badge :tone="$ticket->slaTone()">{{ $ticket->slaLabel() }}</x-badge></td>
                            <td class="px-4 py-3">{{ $ticket->assignedTo?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $ticket)
                                    <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $ticket->id }}', event: 'confirmed-delete-ticket', title: 'Delete ticket?', message: 'The ticket and its conversation will be removed.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No tickets yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
