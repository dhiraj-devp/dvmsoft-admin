@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        'brand' => 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
