<div>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="font-semibold">Assigned team</h3>
        @can('create', App\Models\ProjectMember::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add member</button>
        @endcan
    </div>
    <div class="space-y-3">
        @forelse ($members as $member)
            <div wire:key="{{ $member->id }}" class="flex items-center justify-between rounded-2xl border border-ink-100 px-4 py-3 dark:border-ink-800">
                <div>
                    <p class="font-medium">{{ $member->user?->name }}</p>
                    <p class="text-xs text-ink-500">{{ $member->roleLabel() }}</p>
                </div>
                <div>
                    @can('update', $member)
                        <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $member->id }}')">Edit</button>
                    @endcan
                    @can('delete', $member)
                        <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $member->id }}', event: 'confirmed-delete-member', title: 'Remove team member?', message: 'They will no longer be assigned to this project.' })">Remove</button>
                    @endcan
                </div>
            </div>
        @empty
            <x-empty-state title="No team members yet" />
        @endforelse
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-md overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit member' : 'Add member' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Person</label>
                    <select wire:model="form.user_id" class="input">
                        <option value="">Select</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('form.user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Project role</label>
                    <select wire:model="form.role" class="input">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
