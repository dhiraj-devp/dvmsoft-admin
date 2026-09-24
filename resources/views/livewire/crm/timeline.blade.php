<div>
    <form wire:submit="addNote" class="mb-4">
        <label class="label">Add a note</label>
        <textarea wire:model="note" rows="3" class="input" placeholder="Log a call, meeting, or internal remark"></textarea>
        @error('note')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="btn-secondary mt-2">Add to history</button>
    </form>
    <ol class="space-y-4">
        @forelse ($activities as $activity)
            <li class="border-l-2 border-brand-200 pl-3 dark:border-brand-800">
                <p class="text-sm font-medium">{{ $activity->title }}</p>
                @if ($activity->body)
                    <p class="mt-1 text-sm text-ink-500">{{ $activity->body }}</p>
                @endif
                <p class="mt-1 text-xs text-ink-400">{{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at?->diffForHumans() }}</p>
            </li>
        @empty
            <p class="text-sm text-ink-500">No activity yet.</p>
        @endforelse
    </ol>
</div>
