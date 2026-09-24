<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">AI lead assistant</h2>
            <p class="mt-1 text-sm text-ink-500">Suggestions only. Status is never changed and messages are never sent.</p>
        </div>
        <button type="button" class="btn-secondary" wire:click="generateSummary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generateSummary">Generate Summary</span>
            <span wire:loading wire:target="generateSummary">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" />

    @if ($result)
        <dl class="mt-4 space-y-3 text-sm">
            <div>
                <dt class="text-ink-500">Summary</dt>
                <dd class="mt-1 whitespace-pre-wrap">{{ $result['summary'] ?? '' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Key requirement</dt>
                <dd class="mt-1">{{ $result['key_requirement'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Likely business need</dt>
                <dd class="mt-1">{{ $result['business_need'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Urgency</dt>
                <dd class="mt-1 capitalize">{{ $result['urgency'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Risks</dt>
                <dd class="mt-1">
                    <ul class="list-disc pl-5">
                        @forelse ($this->list('risks') as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>None identified</li>
                        @endforelse
                    </ul>
                </dd>
            </div>
            <div>
                <dt class="text-ink-500">Suggested qualification status</dt>
                <dd class="mt-1 font-medium">{{ $result['suggested_status'] ?? '—' }}</dd>
                <p class="mt-1 text-xs text-ink-400">Review this yourself. AI will not update the lead.</p>
            </div>
            <div>
                <dt class="text-ink-500">Suggested next follow-up</dt>
                <dd class="mt-1">{{ $result['next_follow_up'] ?? '—' }}</dd>
            </div>
            <div>
                <div class="flex items-center justify-between gap-2">
                    <dt class="text-ink-500">Draft follow-up message</dt>
                    <x-ai-copy :text="$result['follow_up_message'] ?? ''" />
                </div>
                <dd class="mt-1 whitespace-pre-wrap rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $result['follow_up_message'] ?? '' }}</dd>
            </div>
        </dl>
    @endif
</div>
