<div>
    <div class="mb-4 flex justify-end">
        @can('create', App\Models\DocumentType::class)
            <button type="button" wire:click="create" class="btn-primary">Add type</button>
        @endcan
    </div>

    <div class="table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Documents</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @forelse ($types as $type)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $type->name }}</td>
                        <td class="px-4 py-3">{{ $type->slug }}</td>
                        <td class="px-4 py-3">{{ $type->documents_count }}</td>
                        <td class="px-4 py-3"><x-badge :tone="$type->is_active ? 'success' : 'neutral'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $type)
                                <button type="button" class="text-sm font-medium text-brand-700" wire:click="edit('{{ $type->id }}')">Edit</button>
                            @endcan
                            @can('delete', $type)
                                <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $type->id }}', event: 'confirmed-delete-document-type', title: 'Delete type?', message: 'Types in use cannot be deleted.' })">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No document types" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div x-show="$wire.showForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Document type</h2>
                <button type="button" wire:click="$set('showForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Name</label>
                    <input type="text" wire:model="form.name" class="input">
                    @error('form.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Slug</label>
                    <input type="text" wire:model="form.slug" class="input" placeholder="Auto from name">
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea wire:model="form.description" rows="3" class="input"></textarea>
                </div>
                <div>
                    <label class="label">Sort order</label>
                    <input type="number" wire:model="form.sort_order" class="input">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-ink-300 text-brand-700">
                    Active
                </label>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
