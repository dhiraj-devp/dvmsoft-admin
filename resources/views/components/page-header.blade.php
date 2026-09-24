@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div class="mb-6">
    @if ($breadcrumbs)
        <nav class="mb-3 flex flex-wrap items-center gap-2 text-sm text-ink-500">
            @foreach ($breadcrumbs as $label => $url)
                @if (! $loop->last && $url)
                    <a href="{{ $url }}" class="hover:text-ink-800 dark:hover:text-white">{{ $label }}</a>
                    <span>/</span>
                @else
                    <span class="text-ink-800 dark:text-ink-200">{{ is_int($label) ? $url : $label }}</span>
                @endif
            @endforeach
        </nav>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-ink-500">{{ $description }}</p>
            @endif
        </div>
        <div>{{ $actions ?? '' }}</div>
    </div>
</div>
