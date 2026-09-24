@extends('layouts.app')

@section('content')
    <x-page-header title="Support overview" description="Tickets, SLAs, and queue health for {{ company_name() }}." :breadcrumbs="['Support' => route('support.dashboard'), 'Overview' => null]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Tickets by priority</h2></div>
            @foreach ($byPriority as $row)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>{{ $row['label'] }}</span>
                    <span class="font-medium">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Tickets by status</h2></div>
            @foreach ($byStatus as $row)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>{{ $row['label'] }}</span>
                    <span class="font-medium">{{ $row['value'] }}</span>
                </div>
            @endforeach
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">My assigned tickets</h2></div>
            @forelse ($mine as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $ticket->number }}</span>
                        <span class="text-xs text-ink-500">{{ $ticket->client?->name }}</span>
                    </span>
                    <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                </a>
            @empty
                <x-empty-state title="No tickets assigned to you" />
            @endforelse
        </section>
    </div>

    <section class="card mt-6">
        <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent activity</h2></div>
        @forelse ($recentActivity as $log)
            <div class="px-5 py-3 text-sm">
                <span class="font-medium">{{ $log->user?->name ?: 'System' }}</span>
                <span class="text-ink-500"> {{ $log->action }} {{ $log->module }}</span>
            </div>
        @empty
            <x-empty-state title="No support activity yet" />
        @endforelse
    </section>
@endsection
