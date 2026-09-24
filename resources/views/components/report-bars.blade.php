@props(['items' => []])

@php
    $max = max(1, (float) collect($items)->max('value'));
@endphp

@forelse ($items as $item)
    <div class="flex items-center gap-3 px-5 py-2.5 text-sm">
        <span class="w-36 shrink-0 text-ink-600">{{ $item['label'] }}</span>
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
            <div class="h-2 rounded-full bg-brand-600" style="width: {{ min(100, round(((float) $item['value'] / $max) * 100, 1)) }}%"></div>
        </div>
        <span class="w-28 shrink-0 text-right font-medium">{{ $item['display'] ?? $item['value'] }}</span>
    </div>
@empty
    <x-empty-state title="No chart data" />
@endforelse
