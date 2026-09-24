<div>
    <form class="card mb-6 flex flex-col gap-3 p-4 lg:flex-row lg:items-end" wire:submit.prevent>
        <div class="sm:max-w-xs">
            <label class="label">Date range</label>
            <select wire:model.live="preset" class="input">
                @foreach ($presets as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
        @if ($preset === 'custom')
            <div>
                <label class="label">From</label>
                <input type="date" wire:model.live="from" class="input">
            </div>
            <div>
                <label class="label">To</label>
                <input type="date" wire:model.live="to" class="input">
            </div>
        @endif
        <p class="text-sm text-ink-500 lg:mb-2">{{ $period->label() }}</p>
        @if ($canExport)
            <a href="{{ $exportUrl }}" class="btn-secondary lg:ml-auto">Export CSV</a>
        @endif
    </form>

    @if (($report['metrics'] ?? []) === [])
        <div class="card p-6">
            <x-empty-state title="No report data for your permissions" description="Ask an administrator to grant the matching reports permission." />
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($report['metrics'] as $metric)
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-ink-500">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 p-2.5 text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                            <x-icon :name="$metric['icon'] ?? 'chart'" />
                        </div>
                    </div>
                    @if (! empty($metric['hint']))
                        <p class="mt-3 text-xs text-ink-400">{{ $metric['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (($report['charts'] ?? []) !== [])
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            @foreach ($report['charts'] as $chart)
                <section class="card">
                    <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                        <h2 class="font-semibold">{{ $chart['title'] }}</h2>
                    </div>
                    <x-report-bars :items="$chart['items']" />
                </section>
            @endforeach
        </div>
    @endif

    @foreach ($report['tables'] ?? [] as $table)
        <section class="card mt-6">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">{{ $table['title'] }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                        <tr>
                            @foreach ($table['headers'] as $header)
                                <th class="px-4 py-3 font-medium">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                        @forelse ($table['rows'] as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td class="px-4 py-3">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($table['headers']) }}"><x-empty-state title="No rows for this period" /></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</div>
