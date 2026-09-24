<div class="mt-3 rounded-2xl border border-dashed border-ink-200 p-3 dark:border-ink-700">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm font-medium">AI requirement assistant</p>
        <button type="button" class="text-sm font-medium text-brand-700" wire:click="analyzeRequirement" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="analyzeRequirement">Analyze Requirement</span>
            <span wire:loading wire:target="analyzeRequirement">Analyzing…</span>
        </button>
    </div>

    <x-ai-status class="mt-2" :generating="$generating" :generated="$generated" :error="$error" :applied="$applied" />

    @if ($result)
        <div class="mt-3 space-y-2 text-sm">
            <div>
                <p class="text-ink-500">Clarified requirement</p>
                <p class="whitespace-pre-wrap">{{ $result['clarified_requirement'] ?? '' }}</p>
            </div>
            <div>
                <p class="text-ink-500">Acceptance criteria</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('acceptance_criteria') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-ink-500">Missing information</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('missing_information') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-ink-500">Risks / edge cases</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('risks') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="text-ink-500">Suggested priority: <span class="font-medium capitalize text-ink-800 dark:text-ink-100">{{ $result['suggested_priority'] ?? '—' }}</span></p>
            @if ($canApply)
                <button type="button" class="btn-primary mt-2" wire:click="applyAnalysis">Apply</button>
            @endif
        </div>
    @endif
</div>
