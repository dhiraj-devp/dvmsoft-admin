@extends('layouts.app')

@section('content')
    <x-page-header title="Documents overview" description="Company registry for contracts, policies, and controlled files." :breadcrumbs="['Documents' => route('documents.dashboard'), 'Overview' => null]">
        <x-slot:actions>
            @can('create', App\Models\Document::class)
                <a href="{{ route('documents.create') }}" class="btn-primary">New document</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

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

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recently uploaded</h2></div>
            @forelse ($recentUploads as $document)
                <a href="{{ route('documents.show', $document) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $document->number }}</span>
                        <span class="text-xs text-ink-500">{{ $document->title }} · {{ $document->type?->name }}</span>
                    </span>
                    <x-badge :tone="$document->status->tone()">{{ $document->status->label() }}</x-badge>
                </a>
            @empty
                <x-empty-state title="No documents yet" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent activity</h2></div>
            @forelse ($recentActivity as $log)
                <div class="px-5 py-3 text-sm">
                    <span class="font-medium">{{ $log->user?->name ?: 'System' }}</span>
                    <span class="text-ink-500"> {{ $log->action }} {{ $log->module }}</span>
                </div>
            @empty
                <x-empty-state title="No document activity yet" />
            @endforelse
        </section>
    </div>
@endsection
