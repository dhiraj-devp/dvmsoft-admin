<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search clients" class="input sm:max-w-xs">
            <select wire:model.live="type" class="input sm:max-w-[10rem]">
                <option value="">All types</option>
                @foreach ($types as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="input sm:max-w-[10rem]">
                <option value="">All statuses</option>
                @foreach ($statuses as $item)
                    <option value="{{ $item->value }}">{{ $item->label() }}</option>
                @endforeach
            </select>
        </div>
        @can('create', App\Models\Client::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add client</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Manager</th>
                        <th class="px-4 py-3 font-medium">Contacts</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($clients as $client)
                        <tr wire:key="{{ $client->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('clients.show', $client) }}" class="font-medium text-brand-700">{{ $client->name }}</a>
                                <div class="text-xs text-ink-500">{{ $client->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $client->type->label() }}</td>
                            <td class="px-4 py-3">{{ $client->accountManager?->name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $client->contacts_count }}</td>
                            <td class="px-4 py-3"><x-badge :tone="$client->status->tone()">{{ $client->status->label() }}</x-badge></td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $client)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $client->id }}')">Edit</button>
                                @endcan
                                @can('delete', $client)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $client->id }}', event: 'confirmed-delete-client', title: 'Delete client?', message: 'The client record will be archived.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No clients yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clients->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $clients->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit client' : 'New client' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Type</label>
                        <select wire:model="form.type" class="input">
                            @foreach ($types as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
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
                <div><label class="label">Name / company</label><input type="text" wire:model="form.name" class="input">@error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Email</label><input type="email" wire:model="form.email" class="input"></div>
                    <div><label class="label">Phone</label><input type="text" wire:model="form.phone" class="input"></div>
                </div>
                <div><label class="label">Website</label><input type="url" wire:model="form.website" class="input">@error('form.website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Address</label><textarea wire:model="form.address" rows="3" class="input"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">GST</label><input type="text" wire:model="form.gst_number" class="input"></div>
                    <div><label class="label">PAN</label><input type="text" wire:model="form.pan_number" class="input"></div>
                </div>
                <div><label class="label">Account manager</label>
                    <select wire:model="form.account_manager_id" class="input">
                        <option value="">Unassigned</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="3" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save client</button>
                </div>
            </form>
        </div>
    </div>
</div>
