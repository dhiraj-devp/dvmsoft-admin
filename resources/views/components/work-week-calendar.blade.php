@props(['days' => []])

@php
    $weeks = collect($days)->chunk(7);
@endphp

<div {{ $attributes }}>
    <div class="grid grid-cols-7 gap-1 px-4 pb-1 pt-3 text-center text-[10px] font-medium uppercase tracking-wide text-ink-400">
        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
    </div>
    <div class="space-y-1 px-4 pb-4">
        @foreach ($weeks as $week)
            <div class="grid grid-cols-7 gap-1">
                @foreach ($week as $day)
                    @php
                        $classes = 'flex h-9 items-center justify-center rounded-lg text-xs';
                        if ($day['future'] || ($day['weeklyOff'] ?? false)) {
                            $classes .= ' text-ink-300 dark:text-ink-600';
                        } elseif ($day['holiday'] ?? false) {
                            $classes .= ' bg-rose-50 font-medium text-rose-700 dark:bg-rose-950 dark:text-rose-200';
                        } elseif ($day['status'] === 'done') {
                            $classes .= ' bg-emerald-100 font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200';
                        } elseif ($day['status'] === 'in_review') {
                            $classes .= ' bg-amber-100 font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-200';
                        } elseif ($day['weekday']) {
                            $classes .= ' bg-ink-50 text-ink-500 dark:bg-ink-800';
                        } else {
                            $classes .= ' text-ink-300 dark:text-ink-600';
                        }
                    @endphp
                    @if ($day['id'])
                        <button type="button" wire:click="read('{{ $day['id'] }}')" class="{{ $classes }}" title="{{ $day['date'] }}">{{ $day['day'] }}</button>
                    @else
                        <span class="{{ $classes }}" title="{{ $day['date'] }}">{{ $day['day'] }}</span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-3 border-t border-ink-100 px-4 py-3 text-xs text-ink-500 dark:border-ink-800">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-ink-50 dark:bg-ink-800"></span> Missed</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-rose-50 dark:bg-rose-950"></span> Holiday</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-100 dark:bg-amber-950"></span> In review</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-100 dark:bg-emerald-950"></span> Done</span>
    </div>
</div>
