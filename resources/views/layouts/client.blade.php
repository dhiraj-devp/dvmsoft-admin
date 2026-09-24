<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    theme: @js(auth('client')->user()->theme ?? request()->cookie('theme', 'system')),
    sidebarOpen: false,
    confirmOpen: false,
    confirmTitle: 'Are you sure?',
    confirmMessage: 'This action cannot be undone.',
    confirmForm: null,
    applyTheme() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = this.theme === 'dark' || (this.theme === 'system' && prefersDark);
        document.documentElement.classList.toggle('dark', isDark);
        localStorage.setItem('theme', this.theme);
    },
    setTheme(value) {
        this.theme = value;
        this.applyTheme();
        fetch(@js(route('client.theme.update')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ theme: value }),
        });
    },
    openConfirm(detail) {
        this.confirmTitle = detail.title || 'Are you sure?';
        this.confirmMessage = detail.message || 'This action cannot be undone.';
        this.confirmForm = detail.form || null;
        this.confirmOpen = true;
    },
    runConfirm() {
        if (this.confirmForm) {
            document.getElementById(this.confirmForm)?.submit();
        }
        this.confirmOpen = false;
    }
}" x-init="applyTheme(); window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => applyTheme())" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? ($companyName ?? 'Dvmsoft') }} · Client Portal</title>
        @if ($favicon = settings()->fileUrl('company.favicon'))
            <link rel="icon" href="{{ $favicon }}">
        @endif
        <script>
            (function () {
                const theme = localStorage.getItem('theme') || @js(auth('client')->user()->theme ?? 'system');
                const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
            })();
        </script>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-full bg-ink-50 text-ink-900 dark:bg-ink-950 dark:text-ink-100">
        <div class="flex min-h-screen">
            @include('layouts.partials.client-sidebar')

            <div class="flex min-w-0 flex-1 flex-col">
                @include('layouts.partials.client-topbar')

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        </div>

        <div x-data="toasts()" @notify.window="add($event)" class="pointer-events-none fixed right-4 top-4 z-50 space-y-2">
            <template x-for="item in items" :key="item.id">
                <div class="pointer-events-auto flex min-w-72 items-start gap-3 rounded-2xl border border-ink-200 bg-white px-4 py-3 shadow-lg dark:border-ink-700 dark:bg-ink-900">
                    <div class="mt-0.5 h-2.5 w-2.5 rounded-full" :class="item.type === 'error' ? 'bg-red-500' : 'bg-brand-500'"></div>
                    <p class="text-sm font-medium" x-text="item.message"></p>
                </div>
            </template>
        </div>

        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4" @keydown.escape.window="confirmOpen = false">
            <div class="card w-full max-w-md p-6" @click.outside="confirmOpen = false">
                <h3 class="text-lg font-semibold" x-text="confirmTitle"></h3>
                <p class="mt-2 text-sm text-ink-500" x-text="confirmMessage"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="confirmOpen = false">Cancel</button>
                    <button type="button" class="btn-danger" @click="runConfirm()">Confirm</button>
                </div>
            </div>
        </div>

        @if (session('status'))
            <script>window.addEventListener('load', () => window.dispatchEvent(new CustomEvent('notify', { detail: { message: @js(session('status')) } })));</script>
        @endif

        @livewireScripts
        <style>[x-cloak]{display:none!important}</style>
    </body>
</html>
