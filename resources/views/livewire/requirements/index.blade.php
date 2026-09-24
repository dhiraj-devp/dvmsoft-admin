<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search requirements" class="input sm:max-w-xs">
        @can('create', App\Models\Requirement::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add requirement</button>
        @endcan
    </div>

    <div class="space-y-3">
        @forelse ($requirements as $requirement)
            <article wire:key="{{ $requirement->id }}" class="rounded-2xl border border-ink-100 p-4 dark:border-ink-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">{{ $requirement->title }}</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $requirement->description }}</p>
                    </div>
                    <div class="flex gap-2">
                        <x-badge :tone="$requirement->priority->tone()">{{ $requirement->priority->label() }}</x-badge>
                        <x-badge :tone="$requirement->status->tone()">{{ $requirement->status->label() }}</x-badge>
                        <x-badge :tone="$requirement->client_approval_status->tone()">{{ $requirement->client_approval_status->label() }}</x-badge>
                    </div>
                </div>
                @if ($requirement->attachments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        @foreach ($requirement->attachments as $file)
                            <a href="{{ route('projects.files.download', [$project, $file]) }}" class="rounded-full bg-ink-50 px-3 py-1 text-brand-700 dark:bg-ink-950">{{ $file->original_name }}</a>
                        @endforeach
                    </div>
                @endif
                <div class="mt-3 text-right">
                    @can('update', $requirement)
                        <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $requirement->id }}')">Edit</button>
                    @endcan
                    @can('delete', $requirement)
                        <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $requirement->id }}', event: 'confirmed-delete-requirement', title: 'Delete requirement?', message: 'This requirement will be archived.' })">Delete</button>
                    @endcan
                </div>
                @can('ai.requirements.use')
                    <livewire:ai.requirement-panel :requirement-id="$requirement->id" :key="'req-ai-'.$requirement->id" />
                @endcan
            </article>
        @empty
            <x-empty-state title="No requirements yet" />
        @endforelse
    </div>
    @if ($requirements->hasPages())
        <div class="mt-4">{{ $requirements->links() }}</div>
    @endif

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit requirement' : 'New requirement' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div><label class="label">Title</label><input type="text" wire:model="form.title" class="input">@error('form.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="form.description" rows="4" class="input"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Priority</label>
                        <select wire:model="form.priority" class="input">
                            @foreach ($priorities as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="form.status" class="input">
                            @foreach ($statuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Client approval</label>
                    <select wire:model="form.client_approval_status" class="input">
                        @foreach ($approvals as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="2" class="input"></textarea></div>
                <div>
                    <label class="label">Attachment</label>
                    <input type="file" wire:model="upload" class="input">
                    @error('upload')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save requirement</button>
                </div>
            </form>
        </div>
    </div>
</div>
