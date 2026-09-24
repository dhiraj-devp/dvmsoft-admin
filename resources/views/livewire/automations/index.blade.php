<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search automations" class="input sm:max-w-xs">
        <select wire:model.live="module" class="input sm:max-w-[12rem]">
            <option value="">All modules</option>
            @foreach ($modules as $item)
                <option value="{{ $item }}">{{ \Illuminate\Support\Str::headline($item) }}</option>
            @endforeach
        </select>
        <select wire:model.live="enabled" class="input sm:max-w-[12rem]">
            <option value="">All statuses</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Automation</th>
                        <th class="px-4 py-3 font-medium">Module</th>
                        <th class="px-4 py-3 font-medium">Trigger</th>
                        <th class="px-4 py-3 font-medium">Channel</th>
                        <th class="px-4 py-3 font-medium">Last run</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($automations as $automation)
                        <tr wire:key="{{ $automation->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('automations.show', $automation) }}" class="font-medium text-brand-700">{{ $automation->name }}</a>
                                <div class="text-xs text-ink-500">{{ $automation->description }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $automation->moduleLabel() }}</td>
                            <td class="px-4 py-3">{{ $automation->triggerLabel() }}</td>
                            <td class="px-4 py-3">{{ $automation->channelLabel() }}</td>
                            <td class="px-4 py-3 text-ink-500">{{ $automation->last_ran_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                @if ($automation->last_status)
                                    <x-badge :tone="$automation->last_status->tone()">{{ $automation->last_status->label() }}</x-badge>
                                @else
                                    <x-badge tone="neutral">Idle</x-badge>
                                @endif
                                @if (! $automation->enabled)
                                    <x-badge tone="neutral">Disabled</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $automation)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="toggle('{{ $automation->id }}')">
                                        {{ $automation->enabled ? 'Disable' : 'Enable' }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No automations yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($automations->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $automations->links() }}</div>
        @endif
    </div>
</div>
