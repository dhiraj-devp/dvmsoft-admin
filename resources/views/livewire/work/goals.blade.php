<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search goals" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[12rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\WorkGoal::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add goal</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Goal</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Due</th>
                        <th class="px-4 py-3 font-medium">Progress</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($goals as $goal)
                        <tr wire:key="{{ $goal->id }}">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $goal->title }}</p>
                                <p class="text-xs text-ink-500">{{ $goal->project?->name ?: 'No project' }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $goal->assignedUser?->name }}</td>
                            <td class="px-4 py-3">{{ $goal->due_date?->format('d M Y') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $goal->progress }}%</td>
                            <td class="px-4 py-3"><x-badge :tone="$goal->status->tone()">{{ $goal->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $goal)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $goal->id }}')">Edit</button>
                                @endcan
                                @can('delete', $goal)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $goal->id }}', event: 'confirmed-delete-work-goal', title: 'Delete goal?', message: 'The goal will be archived.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No goals yet" description="Managers set outcomes and deadlines. Staff plan the daily work themselves." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($goals->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $goals->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit goal' : 'New goal' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div><label class="label">Title</label><input type="text" wire:model="form.title" class="input">@error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="form.description" class="input" rows="3"></textarea></div>
                <div><label class="label">Expected outcome</label><textarea wire:model="form.expected_outcome" class="input" rows="2"></textarea></div>
                @if ($canManage)
                    <div><label class="label">Owner</label>
                        <select wire:model="form.assigned_user_id" class="input">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div><label class="label">Project (optional)</label>
                    <select wire:model="form.project_id" class="input">
                        <option value="">None</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Start</label><input type="date" wire:model="form.start_date" class="input"></div>
                    <div><label class="label">Deadline</label><input type="date" wire:model="form.due_date" class="input"></div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Priority</label>
                        <select wire:model="form.priority" class="input">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label">Status</label>
                        <select wire:model="form.status" class="input">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div><label class="label">Progress %</label><input type="number" min="0" max="100" wire:model="form.progress" class="input"></div>
                <button type="submit" class="btn-primary">Save goal</button>
            </form>
        </div>
    </div>
</div>
