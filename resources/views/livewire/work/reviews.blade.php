<div class="grid gap-6 xl:grid-cols-5">
    <div class="xl:col-span-3 space-y-4">
        @forelse ($updates as $update)
            <article class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $update->user?->name }} · {{ $update->work_date->format('d M Y') }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm text-ink-600">{{ $update->accomplished }}</p>
                    </div>
                    @if ($canManage)
                        <button type="button" class="text-sm font-medium text-brand-700" wire:click="start('{{ $update->id }}')">Review</button>
                    @endif
                </div>
                @if ($update->review)
                    <p class="mt-3 text-sm"><span class="font-medium">Feedback:</span> {{ $update->review->feedback }}</p>
                @endif
            </article>
        @empty
            <x-empty-state title="No updates to review" description="Daily updates appear here after staff submit them." />
        @endforelse
        @if ($updates->hasPages())
            <div>{{ $updates->links() }}</div>
        @endif
    </div>
    <div class="xl:col-span-2">
        @if ($selected)
            <form wire:submit="save" class="card space-y-4 p-5">
                <h2 class="font-semibold">Review {{ $selected->user?->name }}</h2>
                <div><label class="label">Quality (1–5)</label><input type="number" min="1" max="5" wire:model="form.quality_score" class="input"></div>
                <div><label class="label">Feedback</label><textarea wire:model="form.feedback" rows="4" class="input"></textarea>@error('form.feedback')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Action</label><textarea wire:model="form.action_items" rows="3" class="input"></textarea></div>
                <button type="submit" class="btn-primary">Save review</button>
            </form>
        @else
            <p class="text-sm text-ink-500">Select an update to leave feedback.</p>
        @endif
    </div>
</div>
