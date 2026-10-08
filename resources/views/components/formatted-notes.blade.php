@props(['text' => ''])

@php
    $lines = preg_split('/\R/u', trim((string) $text)) ?: [];
    $lines = array_values(array_filter($lines, fn (string $line) => trim($line) !== ''));
@endphp

@if ($lines === [])
    <p {{ $attributes->class('text-ink-400') }}>—</p>
@elseif (count($lines) === 1)
    <p {{ $attributes->class('whitespace-pre-line leading-relaxed text-ink-700 dark:text-ink-200') }}>{{ $lines[0] }}</p>
@else
    <ul {{ $attributes->class('list-disc space-y-1.5 pl-5 leading-relaxed text-ink-700 dark:text-ink-200') }}>
        @foreach ($lines as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ul>
@endif
