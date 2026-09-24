@extends('layouts.client')

@section('content')
    <x-page-header title="{{ $project->name }}" :description="$project->number" :breadcrumbs="['Projects' => route('client.projects.index'), $project->name => null]" />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Status</p>
            <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Health</p>
            <x-badge :tone="$project->health->tone()">{{ $project->health->label() }}</x-badge>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Progress</p>
            <p class="text-lg font-semibold">{{ $progress }}%</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        <div class="card p-4 text-sm">
            <p class="text-ink-500">Timeline</p>
            <p class="font-medium">{{ $project->start_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }} → {{ $project->expected_end_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</p>
        </div>
    </div>

    @if ($project->description)
        <section class="card mb-6 p-5">
            <h2 class="font-semibold">Overview</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-ink-600">{{ $project->description }}</p>
        </section>
    @endif

    <livewire:portal.project-delivery :project-id="$project->id" :key="'client-delivery-'.$project->id" />

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card p-5">
            <h2 class="font-semibold">Milestones</h2>
            @forelse ($project->milestones as $milestone)
                <div class="mt-3 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                    <div>
                        <p class="font-medium">{{ $milestone->name }}</p>
                        <p class="text-xs text-ink-500">Due {{ $milestone->due_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</p>
                    </div>
                    <div class="text-right">
                        <x-badge :tone="$milestone->status->tone()">{{ $milestone->status->label() }}</x-badge>
                        <p class="text-xs text-ink-500">{{ $milestone->completion_percentage }}%</p>
                    </div>
                </div>
            @empty
                <x-empty-state title="No milestones yet" />
            @endforelse
        </section>

        <section class="card p-5">
            <h2 class="font-semibold">Approved requirements</h2>
            @forelse ($project->requirements as $requirement)
                <div class="mt-3 rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                    <p class="font-medium">{{ $requirement->title }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ $requirement->description }}</p>
                    <x-badge class="mt-2" :tone="$requirement->client_approval_status->tone()">{{ $requirement->client_approval_status->label() }}</x-badge>
                </div>
            @empty
                <x-empty-state title="No approved requirements" description="Requirements appear here after they are approved." />
            @endforelse
        </section>
    </div>

    <section class="card mt-6 p-5">
        <h2 class="font-semibold">Shared files</h2>
        @forelse ($files as $file)
            <a href="{{ route('client.projects.files.download', [$project, $file]) }}" class="mt-3 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                <span>{{ $file->original_name }}</span>
                <span class="text-xs text-ink-500">{{ $file->humanSize() }}</span>
            </a>
        @empty
            <x-empty-state title="No files shared yet" description="Files attached to approved requirements will be available here." />
        @endforelse
    </section>
@endsection
