<div>
    @if ($canSeeTeam)
        <div class="mb-4">
            <select wire:model.live="userId" class="input sm:max-w-xs">
                <option value="">All people</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($updates as $update)
            <article class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $update->user?->name }} · {{ $update->work_date->format('d M Y') }}</h2>
                        <p class="text-xs text-ink-500">Submitted {{ $update->submitted_at?->format('d M H:i') ?: '—' }}</p>
                    </div>
                    @if ($update->review)
                        <x-badge tone="success">Reviewed {{ $update->review->quality_score }}/5</x-badge>
                    @endif
                </div>
                <p class="mt-3 whitespace-pre-line text-sm">{{ $update->accomplished }}</p>
                @if ($update->learned)
                    <p class="mt-2 text-sm text-ink-600"><span class="font-medium">Learned:</span> {{ $update->learned }}</p>
                @endif
                @if ($update->blocked)
                    <p class="mt-2 text-sm text-red-700"><span class="font-medium">Blocked:</span> {{ $update->blocked }}</p>
                @endif
            </article>
        @empty
            <x-empty-state title="No daily updates" description="Submit an update from My Work at the end of the day." />
        @endforelse
    </div>
    @if ($updates->hasPages())
        <div class="mt-4">{{ $updates->links() }}</div>
    @endif
</div>
