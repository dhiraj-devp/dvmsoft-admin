@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $document->number }}" description="{{ $document->title }}" :breadcrumbs="['Documents' => route('documents.dashboard'), 'All Documents' => route('documents.index'), $document->number => null]">
        <x-slot:actions>
            @can('update', $document)
                <a href="{{ route('documents.edit', $document) }}" class="btn-secondary">Edit</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Type</p>
            <p class="font-medium">{{ $document->type?->name }}</p>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Status</p>
            <x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Version</p>
            <p class="font-medium">{{ $document->versionLabel() }}</p>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Expiry</p>
            <p class="font-medium">{{ $document->expiry_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card p-5 text-sm xl:col-span-1">
            <h2 class="font-semibold">Details</h2>
            <dl class="mt-4 space-y-3">
                <div><dt class="text-ink-500">Owner</dt><dd class="font-medium">{{ $document->owner?->name ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Created by</dt><dd class="font-medium">{{ $document->createdBy?->name ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Approved by</dt><dd class="font-medium">{{ $document->approvedBy?->name ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Approved at</dt><dd class="font-medium">{{ $document->approved_at?->format(settings('company.date_format', 'd M Y').' H:i') ?: '—' }}</dd></div>
                <div><dt class="text-ink-500">Client</dt><dd class="font-medium">@if ($document->client)<a href="{{ route('clients.show', $document->client) }}" class="text-brand-700">{{ $document->client->name }}</a>@else — @endif</dd></div>
                <div><dt class="text-ink-500">Project</dt><dd class="font-medium">@if ($document->project)<a href="{{ route('projects.show', $document->project) }}" class="text-brand-700">{{ $document->project->number }}</a>@else — @endif</dd></div>
                <div><dt class="text-ink-500">Employee</dt><dd class="font-medium">@if ($document->employee)<a href="{{ route('employees.show', $document->employee) }}" class="text-brand-700">{{ $document->employee->name() }}</a>@else — @endif</dd></div>
                <div><dt class="text-ink-500">Quotation</dt><dd class="font-medium">@if ($document->quotation)<a href="{{ route('quotations.show', $document->quotation) }}" class="text-brand-700">{{ $document->quotation->number }}</a>@else — @endif</dd></div>
                <div><dt class="text-ink-500">Invoice</dt><dd class="font-medium">@if ($document->invoice)<a href="{{ route('invoices.show', $document->invoice) }}" class="text-brand-700">{{ $document->invoice->number }}</a>@else — @endif</dd></div>
            </dl>
            @if ($document->description)
                <div class="mt-5">
                    <h3 class="font-semibold">Description</h3>
                    <p class="mt-2 whitespace-pre-wrap text-ink-600">{{ $document->description }}</p>
                </div>
            @endif
            @if ($document->notes)
                <div class="mt-5">
                    <h3 class="font-semibold">Notes</h3>
                    <p class="mt-2 whitespace-pre-wrap text-ink-600">{{ $document->notes }}</p>
                </div>
            @endif
            @if ($document->approval_notes)
                <div class="mt-5">
                    <h3 class="font-semibold">Approval notes</h3>
                    <p class="mt-2 whitespace-pre-wrap text-ink-600">{{ $document->approval_notes }}</p>
                </div>
            @endif
        </section>
        <section class="xl:col-span-2">
            <livewire:documents.workspace :document-id="$document->id" :key="'doc-'.$document->id" />
        </section>
    </div>
@endsection
