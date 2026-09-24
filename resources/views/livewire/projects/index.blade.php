<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search projects" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[12rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="health" class="input sm:max-w-[10rem]">
                <option value="">All health</option>
                @foreach ($healths as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New project</a>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Manager</th>
                        <th class="px-4 py-3 font-medium">Health</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($projects as $project)
                        <tr wire:key="{{ $project->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $project) }}" class="font-medium text-brand-700">{{ $project->name }}</a>
                                <div class="text-xs text-ink-500">{{ $project->number }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $project->client?->name }}</td>
                            <td class="px-4 py-3">{{ $project->manager?->name ?: '—' }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$project->health->tone()">{{ $project->health->label() }}</x-badge></td>
                            <td class="px-4 py-3"><x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $project)
                                    <a href="{{ route('projects.edit', $project) }}" class="text-sm font-medium text-brand-700">Edit</a>
                                @endcan
                                @can('delete', $project)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $project->id }}', event: 'confirmed-delete-project', title: 'Archive project?', message: 'The project will be moved to the archive.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No projects yet" description="Create a project or convert a quotation." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $projects->links() }}</div>
        @endif
    </div>
</div>
