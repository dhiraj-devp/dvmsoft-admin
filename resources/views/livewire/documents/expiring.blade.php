<div class="table-wrap">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3 font-medium">Document</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Owner</th>
                    <th class="px-4 py-3 font-medium">Expiry</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('documents.show', $document) }}" class="font-medium text-brand-700">{{ $document->number }}</a>
                            <div class="text-xs text-ink-500">{{ $document->title }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $document->type?->name }}</td>
                        <td class="px-4 py-3">{{ $document->owner?->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $document->expiry_date?->format(settings('company.date_format', 'd M Y')) }}
                            @if ($document->isExpired())
                                <x-badge tone="danger">Expired</x-badge>
                            @elseif ($document->isExpiringSoon($warning))
                                <x-badge tone="warning">Soon</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No expiring documents" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($documents->hasPages())
        <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $documents->links() }}</div>
    @endif
</div>
