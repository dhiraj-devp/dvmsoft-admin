@php
    $companyName = $companyName ?? company_name();
    $companyLogo = $companyLogo ?? settings()->fileUrl('company.logo');
    $items = [
        ['label' => 'Dashboard', 'route' => 'client.dashboard', 'match' => 'client.dashboard', 'icon' => 'home'],
        ['label' => 'Projects', 'route' => 'client.projects.index', 'match' => 'client.projects.*', 'icon' => 'briefcase'],
        ['label' => 'Documents', 'route' => 'client.documents.index', 'match' => 'client.documents.*', 'icon' => 'folder'],
        ['label' => 'Quotations', 'route' => 'client.quotations.index', 'match' => 'client.quotations.*', 'icon' => 'document'],
        ['label' => 'Invoices', 'route' => 'client.invoices.index', 'match' => 'client.invoices.*', 'icon' => 'currency'],
        ['label' => 'Payments', 'route' => 'client.payments.index', 'match' => 'client.payments.*', 'icon' => 'check'],
        ['label' => 'Tickets', 'route' => 'client.tickets.index', 'match' => 'client.tickets.*', 'icon' => 'lifebuoy'],
        ['label' => 'Change requests', 'route' => 'client.change-requests.index', 'match' => 'client.change-requests.*', 'icon' => 'alert'],
        ['label' => 'Profile', 'route' => 'client.profile.edit', 'match' => 'client.profile.*', 'icon' => 'users'],
    ];
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
            <p class="text-xs text-ink-500">Client Portal</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ request()->routeIs($item['match']) ? 'bg-brand-50 font-semibold text-brand-800 dark:bg-brand-900/40 dark:text-brand-200' : 'font-medium text-ink-600 hover:bg-ink-50 dark:text-ink-300 dark:hover:bg-ink-800' }}">
                <x-icon :name="$item['icon']" class="h-4.5 w-4.5" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-ink-950/40 lg:hidden" @click="sidebarOpen = false"></div>
