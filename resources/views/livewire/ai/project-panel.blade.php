<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">AI project assistant</h2>
            <p class="mt-1 text-sm text-ink-500">Uses this project’s tasks, milestones, and change requests only.</p>
        </div>
        <button type="button" class="btn-secondary" wire:click="generateSummary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generateSummary">Generate Summary</span>
            <span wire:loading wire:target="generateSummary">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" />

    @if ($result)
        <div class="mt-4 space-y-3 text-sm">
            <div>
                <p class="text-ink-500">Status summary</p>
                <p class="whitespace-pre-wrap">{{ $result['status_summary'] ?? '' }}</p>
            </div>
            @foreach ([
                'overdue_tasks' => 'Overdue tasks',
                'blocked_tasks' => 'Blocked tasks',
                'milestone_risks' => 'Milestone risks',
                'change_request_risks' => 'Change-request risks',
                'next_actions' => 'Suggested next actions',
            ] as $key => $label)
                <div>
                    <p class="text-ink-500">{{ $label }}</p>
                    <ul class="list-disc pl-5">
                        @forelse ($this->list($key) as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>None identified</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>
