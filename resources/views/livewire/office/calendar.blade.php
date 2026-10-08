<div class="space-y-4">
    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
    @endif

    <section class="card p-5">
        <h2 class="font-semibold">Weekly off</h2>
        <p class="mt-1 text-sm text-ink-500">These days are always off. Staff are not expected to submit a report.</p>
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ($weekdays as $iso => $name)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="weeklyOffs" value="{{ $iso }}" class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                    {{ $name }}
                </label>
            @endforeach
        </div>
    </section>

    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-ink-100 px-5 py-3 dark:border-ink-800">
            <button type="button" class="btn-secondary" wire:click="previousMonth">Previous</button>
            <h2 class="font-semibold">{{ $label }}</h2>
            <button type="button" class="btn-secondary" wire:click="nextMonth">Next</button>
        </div>
        <div class="grid grid-cols-[auto_repeat(7,minmax(0,1fr))] gap-1 p-4 text-center text-[10px] font-medium uppercase tracking-wide text-ink-400">
            <span></span>
            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
        </div>
        <div class="space-y-1 px-4 pb-4">
            @foreach ($weeks as $week)
                <div class="grid grid-cols-[auto_repeat(7,minmax(0,1fr))] gap-1">
                    <div class="flex flex-col justify-center gap-1 pr-2">
                        <button type="button" class="text-[10px] font-medium text-brand-700" wire:click="markWeekOff('{{ $week[0]['date'] }}')">Week off</button>
                        <button type="button" class="text-[10px] text-ink-400" wire:click="clearWeekOff('{{ $week[0]['date'] }}')">Clear</button>
                    </div>
                    @foreach ($week as $day)
                        @php
                            $classes = 'flex h-12 flex-col items-center justify-center rounded-lg text-xs';
                            if (! $day['inMonth']) {
                                $classes .= ' text-ink-300 dark:text-ink-600';
                            } elseif ($day['weeklyOff']) {
                                $classes .= ' bg-ink-50 text-ink-400 dark:bg-ink-800';
                            } elseif ($day['holiday']) {
                                $classes .= ' bg-rose-100 font-semibold text-rose-800 dark:bg-rose-950 dark:text-rose-200';
                            } else {
                                $classes .= ' bg-white text-ink-700 hover:bg-brand-50 dark:bg-ink-900 dark:text-ink-200';
                            }
                        @endphp
                        @if ($day['weeklyOff'] || ! $day['inMonth'])
                            <span class="{{ $classes }}">{{ $day['day'] }}</span>
                        @else
                            <button type="button" wire:click="toggleDay('{{ $day['date'] }}')" class="{{ $classes }}" title="{{ $day['holiday'] ? 'Remove holiday' : 'Mark holiday' }}">
                                <span>{{ $day['day'] }}</span>
                                @if ($day['holiday'])
                                    <span class="text-[9px] font-medium">Off</span>
                                @endif
                            </button>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
        <p class="border-t border-ink-100 px-5 py-3 text-sm text-ink-500 dark:border-ink-800">Click a working day to toggle a holiday. Use Week off to close a full week.</p>
    </section>
</div>
