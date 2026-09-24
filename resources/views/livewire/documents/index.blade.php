<div>
    <div class="mb-4 grid gap-3 lg:grid-cols-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search title, number, client, project, employee" class="input lg:col-span-2">
        <select wire:model.live="status" class="input">
            <option value="">All statuses</option>
            @foreach ($statuses as $item)
                <option value="{{ $item->value }}">{{ $item->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeId" class="input">
            <option value="">All types</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="ownerId" class="input">
            <option value="">All owners</option>
            @foreach ($owners as $owner)
                <option value="{{ $owner->id }}">{{ $owner->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="clientId" class="input">
            <option value="">All clients</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="expiry" class="input">
            <option value="">Any expiry</option>
            <option value="expiring">Expiring soon</option>
            <option value="expired">Expired</option>
            <option value="has_expiry">Has expiry date</option>
        </select>
    </div>

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Document</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Related</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Expiry</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($documents as $document)
                        <tr wire:key="{{ $document->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('documents.show', $document) }}" class="font-medium text-brand-700">{{ $document->number }}</a>
                                <div class="text-xs text-ink-500">{{ $document->title }} · {{ $document->versionLabel() }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $document->type?->name }}</td>
                            <td class="px-4 py-3">{{ $document->owner?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-ink-500">
                                {{ $document->client?->name ?: $document->project?->number ?: $document->employee?->name() ?: '—' }}
                            </td>
                            <td class="px-4 py-3"><x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge></td>
                            <td class="px-4 py-3">{{ $document->expiry_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $document)
                                    <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $document->id }}', event: 'confirmed-delete-document', title: 'Delete document?', message: 'The document and its versions will be removed from the registry.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No documents yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($documents->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $documents->links() }}</div>
        @endif
    </div>
</div>
