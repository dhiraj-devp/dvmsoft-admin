<div>
    <p class="mb-4 text-sm text-ink-500">Project files only — the company Documents module is not part of this release.</p>
    @can('create', App\Models\ProjectAttachment::class)
        <form wire:submit="save" class="mb-5 flex flex-col gap-3 sm:flex-row">
            <input type="file" wire:model="upload" class="input">
            <button type="submit" class="btn-primary">Upload</button>
        </form>
        @error('upload')<p class="mb-3 text-sm text-red-600">{{ $message }}</p>@enderror
    @endcan

    <div class="space-y-3">
        @forelse ($files as $file)
            <div wire:key="{{ $file->id }}" class="flex items-center justify-between rounded-2xl border border-ink-100 px-4 py-3 text-sm dark:border-ink-800">
                <div>
                    <a href="{{ route('projects.files.download', [$project, $file]) }}" class="font-medium text-brand-700">{{ $file->original_name }}</a>
                    <p class="text-xs text-ink-500">{{ $file->humanSize() }} · {{ $file->uploadedBy?->name }}</p>
                </div>
                @can('delete', $file)
                    <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $file->id }}', event: 'confirmed-delete-file', title: 'Remove file?', message: 'The file will be archived from this project.' })">Remove</button>
                @endcan
            </div>
        @empty
            <x-empty-state title="No project files yet" />
        @endforelse
    </div>
</div>
