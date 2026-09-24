<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search permissions" class="input sm:max-w-xs">
        <select wire:model.live="group" class="input sm:max-w-xs">
            <option value="">All groups</option>
            @foreach ($groups as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Permission</th>
                        <th class="px-4 py-3 font-medium">Group</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($permissions as $permission)
                        <tr wire:key="{{ $permission->id }}">
                            <td class="px-4 py-3 font-medium">{{ $permission->name }}</td>
                            <td class="px-4 py-3"><x-badge>{{ $permission->group }}</x-badge></td>
                            <td class="px-4 py-3 text-ink-500">{{ $permission->description }}</td>
                            <td class="px-4 py-3">{{ $permission->roles_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-empty-state title="No permissions found" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($permissions->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $permissions->links() }}</div>
        @endif
    </div>
</div>
