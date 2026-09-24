<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">Finance insights</h2>
            <p class="mt-1 text-sm text-ink-500">Operational suggestions from existing invoices and expenses. This is not accounting or legal advice, and records are not modified.</p>
        </div>
        <button type="button" class="btn-primary" wire:click="generateInsights" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generateInsights">Generate Insights</span>
            <span wire:loading wire:target="generateInsights">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" />

    @if ($result)
        <div class="mt-4 space-y-4 text-sm">
            <p class="rounded-xl bg-amber-50 px-3 py-2 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100">{{ $result['disclaimer'] ?? 'These are operational suggestions, not accounting or legal advice.' }}</p>
            <div>
                <p class="font-medium">Outstanding receivables</p>
                <p class="mt-1 whitespace-pre-wrap">{{ $result['receivables_summary'] ?? '' }}</p>
            </div>
            @foreach ([
                'overdue_patterns' => 'Overdue invoice patterns',
                'unusual_expenses' => 'Unusual expense patterns',
                'attention' => 'Clients / projects requiring attention',
                'collection_suggestions' => 'Cash-collection suggestions',
            ] as $key => $label)
                <div>
                    <p class="font-medium">{{ $label }}</p>
                    <ul class="mt-1 list-disc pl-5">
                        @forelse ($this->list($key) as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>None identified from current data.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</div>
