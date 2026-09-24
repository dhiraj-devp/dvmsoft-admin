<div class="card p-5">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold">Company risk summary</h2>
            <p class="mt-1 text-sm text-ink-500">Built from existing sales, project, finance, and support records. Suggestions only.</p>
        </div>
        <button type="button" class="btn-primary" wire:click="generateInsights" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="generateInsights">Generate Insights</span>
            <span wire:loading wire:target="generateInsights">Generating…</span>
        </button>
    </div>

    <x-ai-status :generating="$generating" :generated="$generated" :error="$error" />

    @if ($result)
        <div class="mt-4 grid gap-4 md:grid-cols-2 text-sm">
            @foreach ([
                'sales_risks' => 'Sales risks',
                'project_risks' => 'Project risks',
                'finance_risks' => 'Finance risks',
                'support_risks' => 'Support risks',
                'operational_items' => 'Important operational items',
                'suggested_actions' => 'Suggested actions',
            ] as $key => $label)
                <section class="rounded-2xl border border-ink-100 p-4 dark:border-ink-800">
                    <h3 class="font-semibold">{{ $label }}</h3>
                    <ul class="mt-2 list-disc pl-5">
                        @forelse ($this->list($key) as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>None identified from records you can access.</li>
                        @endforelse
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</div>
