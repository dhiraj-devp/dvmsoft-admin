@php
    $navigation = $navigation ?? [];
    $companyName = $companyName ?? company_name();
    $companyLogo = $companyLogo ?? settings()->fileUrl('company.logo');
@endphp

<aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-ink-200/80 bg-white transition-transform dark:border-ink-800 dark:bg-ink-900 lg:static lg:translate-x-0"
     :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
    <div class="flex h-16 items-center gap-3 border-b border-ink-200/80 px-5 dark:border-ink-800">
        @if ($companyLogo)
            <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="h-9 w-9 rounded-xl object-cover">
        @else
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-sm font-bold text-white">D</div>
        @endif
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold">{{ $companyName }}</p>
            <p class="text-xs text-ink-500">Admin OS</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
        @foreach ($navigation as $item)
            @if (! empty($item['children']))
                <div x-data="{ open: {{ ! empty($item['active']) ? 'true' : 'false' }} }">
                    <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-ink-500 hover:bg-ink-50 hover:text-ink-900 dark:hover:bg-ink-800 dark:hover:text-white" @click="open = !open">
                        <span class="flex items-center gap-3">
                            <x-icon :name="$item['icon'] ?? 'folder'" class="h-4.5 w-4.5" />
                            {{ $item['label'] }}
                        </span>
                        <x-icon name="chevron" class="h-4 w-4 transition" ::class="open ? 'rotate-90' : ''" />
                    </button>
                    <div x-show="open" class="mt-1 space-y-1 pl-4">
                        @foreach ($item['children'] as $child)
                            <a href="{{ $child['url'] }}"
                               class="block rounded-xl px-3 py-2 text-sm {{ ! empty($child['active']) ? 'bg-brand-50 font-semibold text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'text-ink-600 hover:bg-ink-50 dark:text-ink-300 dark:hover:bg-ink-800' }}">
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ $item['url'] }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ ! empty($item['active']) ? 'bg-brand-50 font-semibold text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'font-medium text-ink-600 hover:bg-ink-50 dark:text-ink-300 dark:hover:bg-ink-800' }}">
                    <x-icon :name="$item['icon'] ?? 'home'" class="h-4.5 w-4.5" />
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
</aside>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-ink-950/40 lg:hidden" @click="sidebarOpen = false"></div>
