@props([
    'title' => 'Nothing here yet',
    'description' => 'When data is available, it will appear in this view.',
])

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-ink-50 text-ink-400 dark:bg-ink-800">
        <x-icon name="document" />
    </div>
    <h3 class="text-base font-semibold">{{ $title }}</h3>
    <p class="mt-1 max-w-md text-sm text-ink-500">{{ $description }}</p>
    <div class="mt-4">{{ $slot }}</div>
</div>
