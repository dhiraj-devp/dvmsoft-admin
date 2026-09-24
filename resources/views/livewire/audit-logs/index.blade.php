<div>
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search activity" class="input">
        <select wire:model.live="module" class="input">
            <option value="">All modules</option>
            @foreach ($modules as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
        <select wire:model.live="action" class="input">
            <option value="">All actions</option>
            @foreach ($actions as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">User</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                        <th class="px-4 py-3 font-medium">Module</th>
                        <th class="px-4 py-3 font-medium">Record</th>
                        <th class="px-4 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($logs as $log)
                        <tr x-data="{ open: false }" wire:key="{{ $log->id }}">
                            <td class="px-4 py-3 text-ink-500">{{ $log->created_at?->format(settings('company.date_format', 'd M Y').' H:i') }}</td>
                            <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="px-4 py-3"><x-badge tone="brand">{{ $log->action }}</x-badge></td>
                            <td class="px-4 py-3">{{ $log->module }}</td>
                            <td class="px-4 py-3">
                                <button type="button" class="text-left text-brand-700" @click="open = !open">{{ $log->auditable_id ?? '—' }}</button>
                                <div x-show="open" x-cloak class="mt-2 max-w-md rounded-xl bg-ink-50 p-3 text-xs dark:bg-ink-950">
                                    <pre class="whitespace-pre-wrap">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-500">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><x-empty-state title="No audit events yet" description="Logins, user changes, and settings updates will appear here." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
