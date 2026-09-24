<div>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search contacts" class="input sm:max-w-xs">
            @unless ($lockedToClient)
                <select wire:model.live="clientId" class="input sm:max-w-xs">
                    <option value="">All clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            @endunless
        </div>
        @can('create', App\Models\Contact::class)
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add contact</button>
        @endcan
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($contacts as $contact)
                        <tr wire:key="{{ $contact->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $contact->name }}</div>
                                <div class="text-xs text-ink-500">{{ $contact->job_title }} @if($contact->is_primary)<x-badge tone="brand">Primary</x-badge>@endif</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($contact->client)
                                    <a href="{{ route('clients.show', $contact->client) }}" class="text-brand-700">{{ $contact->client->name }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $contact->email ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $contact->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $contact)
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $contact->id }}')">Edit</button>
                                @endcan
                                @can('delete', $contact)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $contact->id }}', event: 'confirmed-delete-contact', title: 'Delete contact?', message: 'This person will be removed from the client.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No contacts yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($contacts->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $contacts->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit contact' : 'New contact' }}</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Client</label>
                    <select wire:model="form.client_id" class="input" @disabled($lockedToClient)>
                        <option value="">Select client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('form.client_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div><label class="label">Name</label><input type="text" wire:model="form.name" class="input">@error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Email</label><input type="email" wire:model="form.email" class="input"></div>
                    <div><label class="label">Phone</label><input type="text" wire:model="form.phone" class="input"></div>
                </div>
                <div><label class="label">Job title</label><input type="text" wire:model="form.job_title" class="input"></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_primary" class="rounded border-ink-300 text-brand-700"> Primary contact</label>
                <div><label class="label">Notes</label><textarea wire:model="form.notes" rows="3" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save contact</button>
                </div>
            </form>
        </div>
    </div>
</div>
