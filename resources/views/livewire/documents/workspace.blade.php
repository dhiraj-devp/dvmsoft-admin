<div class="space-y-6">
    <section class="card p-5">
        <h2 class="font-semibold">Workflow</h2>
        <p class="mt-1 text-sm text-ink-500">Draft → Review → Approved → Sent → Signed → Archived</p>

        <div class="mt-4">
            <label class="label">Approval notes</label>
            <textarea wire:model="approvalNotes" rows="3" class="input" placeholder="Required when rejecting"></textarea>
            @error('approvalNotes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            @can('submit', $document)
                <button type="button" wire:click="submit" class="btn-primary">Submit for review</button>
            @endcan
            @can('approve', $document)
                <button type="button" wire:click="approve" class="btn-primary">Approve</button>
                <button type="button" wire:click="reject" class="btn-secondary">Reject</button>
            @endcan
            @if ($document->status->value === 'approved')
                @can('update', $document)
                    <button type="button" wire:click="markSent" class="btn-secondary">Mark sent</button>
                @endcan
            @endif
            @if ($document->status->value === 'sent')
                @can('update', $document)
                    <button type="button" wire:click="markSigned" class="btn-secondary">Mark signed</button>
                @endcan
            @endif
            @can('archive', $document)
                <button type="button" wire:click="archive" class="btn-secondary">Archive</button>
            @endcan
            @can('delete', $document)
                <button type="button" class="btn-secondary text-red-600" @click="$dispatch('confirm', { id: '{{ $document->id }}', event: 'confirmed-delete-document', title: 'Delete document?', message: 'The document will be removed from the company registry.' })">Delete</button>
            @endcan
        </div>
    </section>

    @can('upload', $document)
        @if ($document->status->value !== 'archived')
            <section class="card p-5">
                <h2 class="font-semibold">New version</h2>
                <p class="mt-1 text-sm text-ink-500">Previous versions stay available. Uploading after approval returns the document to draft.</p>
                <form wire:submit="uploadVersion" class="mt-4 space-y-4">
                    <div>
                        <label class="label">File</label>
                        <input type="file" wire:model="upload" class="input">
                        @error('upload')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Change notes</label>
                        <input type="text" wire:model="changeNotes" class="input" placeholder="What changed in this version">
                    </div>
                    <button type="submit" class="btn-primary">Upload version</button>
                </form>
            </section>
        @endif
    @endcan

    <section class="card">
        <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Versions</h2></div>
        @forelse ($document->versions as $version)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <div>
                    <p class="font-medium">{{ $version->version_label }} · {{ $version->original_name }}</p>
                    <p class="text-xs text-ink-500">{{ $version->uploadedBy?->name ?: 'Unknown' }} · {{ $version->created_at?->format(settings('company.date_format', 'd M Y').' H:i') }} · {{ $version->humanSize() }}</p>
                    @if ($version->change_notes)
                        <p class="mt-1 text-xs text-ink-500">{{ $version->change_notes }}</p>
                    @endif
                </div>
                @can('download', $document)
                    <a href="{{ route('documents.versions.download', [$document, $version]) }}" class="text-sm font-medium text-brand-700">Download</a>
                @endcan
            </div>
        @empty
            <x-empty-state title="No versions" />
        @endforelse
    </section>
</div>
