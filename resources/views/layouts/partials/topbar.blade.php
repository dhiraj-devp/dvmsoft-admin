<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-ink-200/80 bg-white/90 px-4 backdrop-blur dark:border-ink-800 dark:bg-ink-900/90 sm:px-6">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="rounded-xl p-2 text-ink-500 hover:bg-ink-50 lg:hidden dark:hover:bg-ink-800" @click="sidebarOpen = true">
            <x-icon name="menu" />
        </button>
        <div class="hidden items-center gap-2 rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-400 md:flex dark:border-ink-700 dark:bg-ink-950">
            <x-icon name="search" class="h-4 w-4" />
            <span>Search the operating system</span>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <div class="relative" x-data="{ open: false }">
            <button type="button" class="rounded-xl p-2 text-ink-500 hover:bg-ink-50 dark:hover:bg-ink-800" @click="open = !open">
                <x-icon name="sun" class="h-5 w-5" x-show="theme !== 'dark'" />
                <x-icon name="moon" class="h-5 w-5" x-show="theme === 'dark'" x-cloak />
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-40 overflow-hidden rounded-xl border border-ink-200 bg-white py-1 shadow-lg dark:border-ink-700 dark:bg-ink-900">
                @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                    <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-ink-50 dark:hover:bg-ink-800" @click="setTheme('{{ $value }}'); open = false">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <livewire:notifications.bell />

        <div class="relative" x-data="{ open: false }">
            <button type="button" class="flex items-center gap-2 rounded-xl p-1.5 hover:bg-ink-50 dark:hover:bg-ink-800" @click="open = !open">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-xs font-semibold text-white">{{ auth()->user()->initials() }}</span>
                <span class="hidden text-left text-sm lg:block">
                    <span class="block font-semibold">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-ink-500">{{ auth()->user()->job_title ?: 'Team member' }}</span>
                </span>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-ink-200 bg-white py-2 shadow-lg dark:border-ink-700 dark:bg-ink-900">
                <div class="px-4 py-2">
                    <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-ink-500">{{ auth()->user()->email }}</p>
                </div>
                @can('settings.view')
                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm hover:bg-ink-50 dark:hover:bg-ink-800">Settings</a>
                @endcan
                <a href="{{ route('notifications.preferences') }}" class="block px-4 py-2 text-sm hover:bg-ink-50 dark:hover:bg-ink-800">Notification preferences</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                        <x-icon name="logout" class="h-4 w-4" /> Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
