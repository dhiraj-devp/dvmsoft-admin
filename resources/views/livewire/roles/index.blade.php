<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search roles" class="input sm:max-w-xs">
        @can('create', App\Models\Role::class)
            <button type="button" wire:click="create" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Add role
            </button>
        @endcan
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($roles as $role)
            <article wire:key="{{ $role->id }}" class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">{{ $role->name }}</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $role->description }}</p>
                    </div>
                    <x-badge tone="brand">{{ $role->permissions_count }} permissions</x-badge>
                </div>
                <p class="mt-4 text-xs text-ink-400">{{ $role->users_count }} people assigned</p>
                <div class="mt-4 flex gap-3">
                    @can('update', $role)
                        <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $role->id }}')">Edit</button>
                    @endcan
                    @can('delete', $role)
                        <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $role->id }}', event: 'confirmed-delete-role', title: 'Delete role?', message: 'Roles with assigned users cannot be deleted.' })">Delete</button>
                    @endcan
                </div>
            </article>
        @empty
            <div class="card md:col-span-2 xl:col-span-3">
                <x-empty-state title="No roles found" />
            </div>
        @endforelse
    </div>

    @if ($roles->hasPages())
        <div class="mt-4">{{ $roles->links() }}</div>
    @endif

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit role' : 'New role' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Name</label>
                    <input type="text" wire:model="form.name" class="input">
                    @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Description</label>
                    <input type="text" wire:model="form.description" class="input">
                </div>
                <div>
                    <label class="label">Permissions</label>
                    <div class="max-h-[28rem] space-y-4 overflow-y-auto rounded-2xl border border-ink-200 p-4 dark:border-ink-700">
                        @foreach ($permissions as $group => $groupPermissions)
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{{ $group }}</p>
                                <div class="grid gap-2">
                                    @foreach ($groupPermissions as $permission)
                                        <label class="flex items-start gap-2 text-sm">
                                            <input type="checkbox" value="{{ $permission->id }}" wire:model="form.permissions" class="mt-0.5 rounded border-ink-300 text-brand-700">
                                            <span>
                                                <span class="font-medium">{{ $permission->name }}</span>
                                                <span class="block text-xs text-ink-500">{{ $permission->description }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('form.permissions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save role</button>
                </div>
            </form>
        </div>
    </div>
</div>
