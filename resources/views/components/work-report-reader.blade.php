@props(['report'])

<div {{ $attributes->class('space-y-5') }}>
    <div>
        <p class="text-sm text-ink-500">{{ $report->work_date->format('l, d M Y') }}</p>
        <x-badge class="mt-2" :tone="$report->status->tone()">{{ $report->status->label() }}</x-badge>
    </div>

    <section>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-500">What was done</h3>
        <div class="mt-2">
            <x-formatted-notes :text="$report->accomplished" />
        </div>
    </section>

    @if ($report->pending)
        <section>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-500">Pending</h3>
            <div class="mt-2">
                <x-formatted-notes :text="$report->pending" />
            </div>
        </section>
    @endif

    @if ($report->learned)
        <section>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-500">Learned</h3>
            <div class="mt-2">
                <x-formatted-notes :text="$report->learned" />
            </div>
        </section>
    @endif

    @if ($report->notes)
        <section>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-ink-500">Better than last week</h3>
            <div class="mt-2">
                <x-formatted-notes :text="$report->notes" />
            </div>
        </section>
    @endif

    @if ($report->manager_stamp)
        <x-badge :tone="$report->manager_stamp->tone()">{{ $report->manager_stamp->label() }}</x-badge>
    @endif

    {{ $slot }}
</div>
