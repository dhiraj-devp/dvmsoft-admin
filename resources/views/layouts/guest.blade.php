<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Staff sign in' }} · {{ $companyName ?? company_name() }} Admin</title>
        @if ($favicon = settings()->fileUrl('company.favicon'))
            <link rel="icon" href="{{ $favicon }}">
        @endif
        <script>
            (function () {
                const theme = localStorage.getItem('theme') || 'system';
                const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
            })();
        </script>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-ink-50 text-ink-900 dark:bg-ink-950 dark:text-ink-100">
        {{ $slot ?? '' }}
        @yield('content')
    </body>
</html>
