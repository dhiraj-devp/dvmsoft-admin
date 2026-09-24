<div class="space-y-6">
    <section class="card p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge tone="brand">{{ $automation->moduleLabel() }}</x-badge>
                    <x-badge tone="neutral">{{ $automation->triggerLabel() }}</x-badge>
                    @if ($automation->enabled)
                        <x-badge tone="success">Enabled</x-badge>
                    @else
                        <x-badge tone="neutral">Disabled</x-badge>
                    @endif
                    @if ($automation->last_status)
                        <x-badge :tone="$automation->last_status->tone()">{{ $automation->last_status->label() }}</x-badge>
                    @endif
                </div>
                <p class="mt-3 text-sm text-ink-500">Last run {{ $automation->last_ran_at?->diffForHumans() ?? 'never' }}. Recipients follow existing ownership and permissions.</p>
                @if ($automation->last_error)
                    <p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-200">{{ $automation->last_error }}</p>
                @endif
            </div>
            @can('update', $automation)
                <button type="button" class="btn-primary" wire:click="toggle">{{ $automation->enabled ? 'Disable' : 'Enable' }}</button>
            @endcan
        </div>
    </section>

    @can('update', $automation)
        <section class="card p-5">
            <h2 class="font-semibold">Notification channels</h2>
            <p class="mt-1 text-sm text-ink-500">Company and per-user preferences still apply. Empty channels fall back to in-app.</p>
            <form wire:submit="saveChannels" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="inApp" class="rounded border-ink-300 text-brand-700">
                    In-app
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="email" class="rounded border-ink-300 text-brand-700">
                    Email
                </label>
                <button type="submit" class="btn-primary sm:ml-auto">Save channels</button>
            </form>
        </section>
    @endcan

    @if ($runs)
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Execution history</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                        <tr>
                            <th class="px-4 py-3 font-medium">Started</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Processed</th>
                            <th class="px-4 py-3 font-medium">Notified</th>
                            <th class="px-4 py-3 font-medium">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                        @forelse ($runs as $run)
                            <tr>
                                <td class="px-4 py-3">{{ $run->started_at?->format(settings('company.date_format', 'd M Y').' H:i') }}</td>
                                <td class="px-4 py-3"><x-badge :tone="$run->status->tone()">{{ $run->status->label() }}</x-badge></td>
                                <td class="px-4 py-3">{{ $run->processed_count }}</td>
                                <td class="px-4 py-3">{{ $run->notified_count }}</td>
                                <td class="px-4 py-3 text-ink-500">{{ $run->error ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state title="No executions yet" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($runs->hasPages())
                <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $runs->links() }}</div>
            @endif
        </section>
    @endif
</div>
