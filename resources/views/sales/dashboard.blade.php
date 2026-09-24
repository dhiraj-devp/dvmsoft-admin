@extends('layouts.app')

@section('content')
    <x-page-header title="Sales overview" description="Pipeline health for Dvmsoft." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Overview' => null]" />

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
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent leads</h2></div>
            @forelse ($recentLeads as $lead)
                <a href="{{ route('leads.show', $lead) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $lead->name }}</span>
                        <span class="text-xs text-ink-500">{{ $lead->company }}</span>
                    </span>
                    <x-badge :tone="$lead->status->tone()">{{ $lead->status->label() }}</x-badge>
                </a>
            @empty
                <x-empty-state title="No leads yet" description="Create the first lead to start the pipeline." />
            @endforelse
        </section>
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent quotations</h2></div>
            @forelse ($recentQuotations as $quotation)
                <a href="{{ route('quotations.show', $quotation) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $quotation->number }}</span>
                        <span class="text-xs text-ink-500">{{ $quotation->client?->name }}</span>
                    </span>
                    <span class="text-xs">{{ money($quotation->total) }}</span>
                </a>
            @empty
                <x-empty-state title="No quotations yet" />
            @endforelse
        </section>
        <section class="card xl:col-span-1">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Upcoming follow-ups</h2></div>
            @forelse ($upcomingFollowUps as $followUp)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>
                        <span class="block font-medium">{{ $followUp->subjectName() }}</span>
                        <span class="text-xs text-ink-500">{{ $followUp->type->label() }} · {{ $followUp->scheduled_at?->format('d M H:i') }}</span>
                    </span>
                    @if ($followUp->isOverdue())
                        <x-badge tone="danger">Overdue</x-badge>
                    @else
                        <x-badge tone="warning">Pending</x-badge>
                    @endif
                </div>
            @empty
                <x-empty-state title="No pending follow-ups" />
            @endforelse
        </section>
    </div>
@endsection
