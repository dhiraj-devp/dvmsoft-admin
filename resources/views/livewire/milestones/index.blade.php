<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search milestones" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[12rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\Milestone::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add milestone</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Milestone</th>
                        @unless ($lockedToProject)
                            <th class="px-4 py-3 font-medium">Project</th>
                        @endunless
                        <th class="px-4 py-3 font-medium">Due</th>
                        <th class="px-4 py-3 font-medium">Progress</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($milestones as $milestone)
                        <tr wire:key="{{ $milestone->id }}">
                            <td class="px-4 py-3 font-medium">{{ $milestone->name }}</td>
                            @unless ($lockedToProject)
                                <td class="px-4 py-3"><a href="{{ route('projects.show', ['project' => $milestone->project, 'tab' => 'milestones']) }}" class="text-brand-700">{{ $milestone->project?->number }}</a></td>
                            @endunless
                            <td class="px-4 py-3">{{ $milestone->due_date?->format('d M Y') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $milestone->completion_percentage }}%</td>
                            <td class="px-4 py-3"><x-badge :tone="$milestone->status->tone()">{{ $milestone->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $milestone)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $milestone->id }}')">Edit</button>
                                @endcan
                                @can('delete', $milestone)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $milestone->id }}', event: 'confirmed-delete-milestone', title: 'Delete milestone?', message: 'This milestone will be archived.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No milestones" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($milestones->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $milestones->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit milestone' : 'New milestone' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                @unless ($lockedToProject)
                    <div>
                        <label class="label">Project</label>
                        <select wire:model="form.project_id" class="input">
                            <option value="">Select</option>
                            @foreach ($projects as $item)
                                <option value="{{ $item->id }}">{{ $item->number }} · {{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('form.project_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endunless
                <div>
                    <label class="label">Name</label>
                    <input type="text" wire:model="form.name" class="input">
                    @error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="label">Description</label><textarea wire:model="form.description" rows="3" class="input"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Due date</label><input type="date" wire:model="form.due_date" class="input"></div>
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="form.status" class="input">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><label class="label">Completion %</label><input type="number" min="0" max="100" wire:model="form.completion_percentage" class="input"></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>
