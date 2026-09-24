@extends('layouts.app')

@section('content')
    <x-page-header title="Projects overview" description="Delivery health across active work." :breadcrumbs="['Projects' => route('projects.dashboard'), 'Overview' => null]" />

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
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent projects</h2></div>
            @forelse ($recentProjects as $project)
                <a href="{{ route('projects.show', $project) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $project->name }}</span>
                        <span class="text-xs text-ink-500">{{ $project->number }} · {{ $project->client?->name }}</span>
                    </span>
                    <x-badge :tone="$project->health->tone()">{{ $project->health->label() }}</x-badge>
                </a>
            @empty
                <x-empty-state title="No projects yet" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">My open tasks</h2></div>
            @forelse ($myTasks as $task)
                <a href="{{ route('projects.show', ['project' => $task->project, 'tab' => 'tasks']) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $task->title }}</span>
                        <span class="text-xs text-ink-500">{{ $task->project?->number }} · {{ $task->due_date?->format('d M') }}</span>
                    </span>
                    <x-badge :tone="$task->status->tone()">{{ $task->status->label() }}</x-badge>
                </a>
            @empty
                <x-empty-state title="No tasks assigned to you" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Upcoming deadlines</h2></div>
            @forelse ($upcomingDeadlines as $task)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span>
                        <span class="block font-medium">{{ $task->title }}</span>
                        <span class="text-xs text-ink-500">{{ $task->project?->name }} · {{ $task->due_date?->format('d M Y') }}</span>
                    </span>
                    @if ($task->isOverdue())
                        <x-badge tone="danger">Overdue</x-badge>
                    @else
                        <x-badge tone="warning">Due</x-badge>
                    @endif
                </div>
            @empty
                <x-empty-state title="No upcoming deadlines" />
            @endforelse
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Waiting for client approval</h2></div>
            @forelse ($awaitingReview as $stage)
                <a href="{{ route('projects.show', ['project' => $stage->project, 'tab' => 'delivery']) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $stage->name }}</span>
                        <span class="text-xs text-ink-500">{{ $stage->project?->number }} · {{ $stage->project?->name }}</span>
                    </span>
                    <x-badge tone="warning">Ready for review</x-badge>
                </a>
            @empty
                <x-empty-state title="No stages awaiting review" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Changes requested</h2></div>
            @forelse ($changesRequested as $stage)
                <a href="{{ route('projects.show', ['project' => $stage->project, 'tab' => 'delivery']) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $stage->name }}</span>
                        <span class="text-xs text-ink-500">{{ $stage->project?->number }} · {{ $stage->project?->name }}</span>
                    </span>
                    <x-badge tone="danger">Changes requested</x-badge>
                </a>
            @empty
                <x-empty-state title="No change requests on stages" />
            @endforelse
        </section>
    </div>
@endsection
