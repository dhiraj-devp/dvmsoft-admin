<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">AI quotation assistant</h2>
            <p class="mt-1 text-sm text-ink-500">Suggests wording only. Prices and totals are never invented or changed.</p>
        </div>
        <button type="button" class="btn-secondary" wire:click="generateDraft" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generateDraft">Suggest Scope</span>
            <span wire:loading wire:target="generateDraft">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" :applied="$applied" />

    @if ($result)
        <div class="mt-4 space-y-3 text-sm">
            <div>
                <p class="text-ink-500">Suggested title</p>
                <p class="font-medium">{{ $result['title'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-ink-500">Suggested line-item descriptions</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('line_item_descriptions') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-ink-500">Missing pricing information</p>
                <ul class="list-disc pl-5">
                    @foreach ($this->list('missing_pricing_information') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="flex items-center justify-between gap-2">
                    <p class="text-ink-500">Draft payment-term wording</p>
                    <x-ai-copy :text="$result['payment_terms_wording'] ?? ''" />
                </div>
                <p class="mt-1 whitespace-pre-wrap rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $result['payment_terms_wording'] ?? '' }}</p>
            </div>
            @if ($canApplyToForm)
                <button type="button" class="btn-primary" wire:click="applyToForm">Apply</button>
            @endif
        </div>
    @endif
</div>
