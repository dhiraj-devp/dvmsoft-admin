<div>
    <div class="mb-4 flex justify-end">
        <button type="button" class="btn-primary" wire:click="create">Add portal user</button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-5 space-y-3 rounded-2xl border border-ink-100 p-4 dark:border-ink-800">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="label">Name</label>
                    <input wire:model="form.name" class="input">
                    @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" wire:model="form.email" class="input">
                    @error('form.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Password</label>
                    <input type="password" wire:model="form.password" class="input">
                    @error('form.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm sm:mt-7">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-ink-300 text-brand-700">
                    Active
                </label>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary">Create</button>
                <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
            </div>
        </form>
    @endif

    @forelse ($users as $user)
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
            <div>
                <p class="font-medium">{{ $user->name }}</p>
                <p class="text-xs text-ink-500">{{ $user->email }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge :tone="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-badge>
                <button type="button" class="btn-secondary" wire:click="toggleActive('{{ $user->id }}')">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                <button type="button" class="btn-secondary" wire:click="sendReset('{{ $user->id }}')">Send reset</button>
                <button type="button" class="btn-danger" @click="$dispatch('confirm', { title: 'Remove portal user?', message: 'They will no longer be able to sign in.', event: 'confirmed-delete-portal-user', id: '{{ $user->id }}' })">Remove</button>
            </div>
        </div>
    @empty
        <p class="text-sm text-ink-500">No portal users yet.</p>
    @endforelse
</div>
