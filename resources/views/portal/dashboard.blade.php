@extends('layouts.client')

@section('content')
    <x-page-header title="Welcome back" :description="'Hello, '.auth('client')->user()->name.'. Here is the latest on your account.'" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-ink-500">{{ $metric['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 p-2.5 text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                        <x-icon :name="$metric['icon']" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink-400">{{ $metric['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Active projects</h2>
            </div>
            @forelse ($activeProjects as $project)
                <a href="{{ route('client.projects.show', $project) }}" class="flex items-center justify-between gap-4 px-5 py-4 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $project->name }}</p>
                        <p class="text-xs text-ink-500">{{ $project->number }}</p>
                        <div class="mt-2 h-2 w-48 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $project->progressPercent() }}%"></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                        <p class="mt-1 text-xs text-ink-500">{{ $project->progressPercent() }}%</p>
                    </div>
                </a>
            @empty
                <x-empty-state title="No active projects" description="When delivery starts, progress will appear here." />
            @endforelse
        </section>

        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Needs attention</h2>
            </div>
            <div class="space-y-3 p-5 text-sm">
                <a href="{{ route('client.quotations.index') }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>Pending quotations</span>
                    <span class="font-semibold">{{ $pendingQuotations }}</span>
                </a>
                <a href="{{ route('client.invoices.index') }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>Outstanding invoices</span>
                    <span class="font-semibold">{{ $outstandingCount }}</span>
                </a>
                <a href="{{ route('client.tickets.index') }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>Open tickets</span>
                    <span class="font-semibold">{{ $openTickets }}</span>
                </a>
                <a href="{{ route('client.change-requests.index') }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>Pending change requests</span>
                    <span class="font-semibold">{{ $pendingChangeRequests }}</span>
                </a>
                <a href="{{ route('client.projects.index') }}" class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 dark:border-ink-800">
                    <span>Stages awaiting approval</span>
                    <span class="font-semibold">{{ $awaitingStages->count() }}</span>
                </a>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Needs your review</h2>
            </div>
            @forelse ($awaitingStages as $stage)
                <a href="{{ route('client.projects.show', $stage->project) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $stage->name }}</span>
                        <span class="text-xs text-ink-500">{{ $stage->project?->name }}</span>
                    </span>
                    <x-badge tone="warning">Review</x-badge>
                </a>
            @empty
                <x-empty-state title="Nothing waiting for review" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Recently completed stages</h2>
            </div>
            @forelse ($recentlyCompletedStages as $stage)
                <a href="{{ route('client.projects.show', $stage->project) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $stage->name }}</span>
                        <span class="text-xs text-ink-500">{{ $stage->project?->name }}</span>
                    </span>
                    <x-badge tone="success">Completed</x-badge>
                </a>
            @empty
                <x-empty-state title="No recently completed stages" />
            @endforelse
        </section>
    </div>

    <section class="card mt-6">
        <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
            <h2 class="font-semibold">Recent activity</h2>
        </div>
        @forelse ($recent as $item)
            <a href="{{ $item['url'] }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                <span>
                    <span class="block font-medium">{{ $item['label'] }}</span>
                    <span class="text-xs text-ink-500">{{ $item['meta'] }} · {{ $item['at']?->diffForHumans() }}</span>
                </span>
                <x-badge :tone="$item['tone']">{{ $item['status'] }}</x-badge>
            </a>
        @empty
            <x-empty-state title="No recent activity" description="Quotations, invoices, tickets, and projects will show up here." />
        @endforelse
    </section>
@endsection
