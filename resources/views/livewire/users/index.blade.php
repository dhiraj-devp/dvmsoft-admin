<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search users" class="input sm:max-w-xs">
            <select wire:model.live="status" class="input sm:max-w-[10rem]">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select wire:model.live="roleId" class="input sm:max-w-[14rem]">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\User::class)
            <button type="button" wire:click="create" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Add user
            </button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Department</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($users as $user)
                        <tr wire:key="{{ $user->id }}" class="hover:bg-ink-50/70 dark:hover:bg-ink-800/40">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-xs text-ink-500">{{ $user->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $user->department?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $user)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $user->id }}')">Edit</button>
                                @endcan
                                @can('delete', $user)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $user->id }}', event: 'confirmed-delete-user', title: 'Delete user?', message: 'This will deactivate the employee record.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state title="No users found" description="Adjust filters or add the first employee." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $users->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit user' : 'New user' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Name</label>
                    <input type="text" wire:model="form.name" class="input">
                    @error('form.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" wire:model="form.email" class="input">
                    @error('form.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Password {{ $editingId ? '(optional)' : '' }}</label>
                        <input type="password" wire:model="form.password" class="input" autocomplete="new-password">
                        @error('form.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Confirm password</label>
                        <input type="password" wire:model="form.password_confirmation" class="input" autocomplete="new-password">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Phone</label>
                        <input type="text" wire:model="form.phone" class="input">
                    </div>
                    <div>
                        <label class="label">Employee code</label>
                        <input type="text" wire:model="form.employee_code" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Job title</label>
                    <input type="text" wire:model="form.job_title" class="input">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Department</label>
                        <select wire:model="form.department_id" class="input">
                            <option value="">Unassigned</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Manager</label>
                        <select wire:model="form.manager_id" class="input">
                            <option value="">None</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Date of joining</label>
                    <input type="date" wire:model="form.date_of_joining" class="input">
                </div>
                <div>
                    <label class="label">Roles</label>
                    <div class="grid max-h-40 gap-2 overflow-y-auto rounded-xl border border-ink-200 p-3 dark:border-ink-700">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" value="{{ $role->id }}" wire:model="form.roles" class="rounded border-ink-300 text-brand-700">
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('form.roles') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-ink-300 text-brand-700">
                    Active account
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Save user</button>
                </div>
            </form>
        </div>
    </div>
</div>
