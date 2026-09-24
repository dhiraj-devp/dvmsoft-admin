@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $ticket->number }}" description="{{ $ticket->subject }}" :breadcrumbs="['Support' => route('support.dashboard'), 'Tickets' => route('tickets.index'), $ticket->number => null]">
        <x-slot:actions>
            @can('update', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}" class="btn-secondary">Edit</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Client</p>
            <a href="{{ route('clients.show', $ticket->client) }}" class="font-medium text-brand-700">{{ $ticket->client?->name }}</a>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Project</p>
            @if ($ticket->project)
                <a href="{{ route('projects.show', $ticket->project) }}" class="font-medium text-brand-700">{{ $ticket->project->number }}</a>
            @else
                <p class="font-medium">None</p>
            @endif
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">SLA deadline</p>
            <p class="font-medium">{{ $ticket->sla_due_at?->format(settings('company.date_format', 'd M Y').' H:i') ?: '—' }}</p>
            <x-badge :tone="$ticket->slaTone()">{{ $ticket->slaLabel() }}</x-badge>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Status</p>
            <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
            <x-badge class="ml-1" :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-5 text-sm xl:col-span-1">
            <h2 class="font-semibold">Details</h2>
            <dl class="mt-4 space-y-3">
                <div><dt class="text-ink-500">Category</dt><dd class="font-medium">{{ $ticket->category?->name ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Assigned to</dt><dd class="font-medium">{{ $ticket->assignedTo?->name ?: 'Unassigned' }}</dd></div>
                <div><dt class="text-ink-500">Created by</dt><dd class="font-medium">{{ $ticket->openerName() }}</dd></div>
                <div><dt class="text-ink-500">Opened</dt><dd class="font-medium">{{ $ticket->created_at?->format(settings('company.date_format', 'd M Y').' H:i') }}</dd></div>
                <div><dt class="text-ink-500">Resolved</dt><dd class="font-medium">{{ $ticket->resolved_at?->format(settings('company.date_format', 'd M Y').' H:i') ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Closed</dt><dd class="font-medium">{{ $ticket->closed_at?->format(settings('company.date_format', 'd M Y').' H:i') ?: '—' }}</dd></div>
            </dl>
            <div class="mt-5">
                <h3 class="font-semibold">Description</h3>
                <p class="mt-2 whitespace-pre-wrap text-ink-600">{{ $ticket->description }}</p>
            </div>
            @if ($ticket->resolution)
                <div class="mt-5">
                    <h3 class="font-semibold">Resolution</h3>
                    <p class="mt-2 whitespace-pre-wrap text-ink-600">{{ $ticket->resolution }}</p>
                </div>
            @endif
        </section>
        <section class="xl:col-span-2 space-y-6">
            @can('ai.tickets.use')
                <livewire:ai.ticket-panel :ticket-id="$ticket->id" :key="'ticket-ai-'.$ticket->id" />
            @endcan
            <livewire:tickets.conversation :ticket-id="$ticket->id" :key="'convo-'.$ticket->id" />
        </section>
    </div>
@endsection
