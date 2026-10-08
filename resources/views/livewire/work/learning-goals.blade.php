<div>
    <div class="mb-4 flex justify-end">
        @can('create', App\Models\WorkLearningGoal::class)
            <button type="button" wire:click="create" class="btn-secondary"><x-icon name="plus" class="h-4 w-4" /> Add learning goal</button>
        @endcan
    </div>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Learning goal</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Target</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($goals as $goal)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $goal->title }}</td>
                            <td class="px-4 py-3">{{ $goal->assignedUser?->name }}</td>
                            <td class="px-4 py-3">{{ $goal->target_date?->format('d M Y') ?: '—' }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$goal->status->tone()">{{ $goal->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $goal)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $goal->id }}')">Edit</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No learning goals" description="Optional goals such as Laravel auth or Git basics." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit learning goal' : 'New learning goal' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div><label class="label">Title</label><input type="text" wire:model="form.title" class="input">@error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="form.description" class="input" rows="3"></textarea></div>
                @if ($canManage)
                    <div><label class="label">Assigned to</label>
                        <select wire:model="form.assigned_user_id" class="input">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div><label class="label">Target date</label><input type="date" wire:model="form.target_date" class="input"></div>
                <div><label class="label">Status</label>
                    <select wire:model="form.status" class="input">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
