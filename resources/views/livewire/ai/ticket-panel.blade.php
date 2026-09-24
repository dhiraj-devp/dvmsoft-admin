<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">AI support assistant</h2>
            <p class="mt-1 text-sm text-ink-500">Drafts a client-safe reply. Internal notes are never sent and the reply is never posted automatically.</p>
        </div>
        <button type="button" class="btn-secondary" wire:click="draftReply" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="draftReply">Draft Reply</span>
            <span wire:loading wire:target="draftReply">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" :applied="$applied" />

    @if ($result)
        <div class="mt-4 space-y-3 text-sm">
            <div>
                <p class="text-ink-500">Conversation summary</p>
                <p class="whitespace-pre-wrap">{{ $result['conversation_summary'] ?? '' }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-ink-500">Likely category</p>
                    <p class="font-medium">{{ $result['suggested_category'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-ink-500">Suggested priority</p>
                    <p class="font-medium capitalize">{{ $result['suggested_priority'] ?? '—' }}</p>
                </div>
            </div>
            <div>
                <p class="text-ink-500">Troubleshooting steps</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('troubleshooting_steps') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-ink-500">Draft reply</p>
                    <div class="flex gap-2">
                        <x-ai-copy :text="$result['draft_reply'] ?? ''" />
                        @if ($canFillReply)
                            <button type="button" class="btn-primary text-xs" wire:click="fillReply">Apply</button>
                        @endif
                    </div>
                </div>
                <p class="mt-1 whitespace-pre-wrap rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $result['draft_reply'] ?? '' }}</p>
            </div>
        </div>
    @endif
</div>
