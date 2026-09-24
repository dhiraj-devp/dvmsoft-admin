@extends('layouts.client')

@section('content')
    <x-page-header title="Projects" description="Status, health, and progress for your delivery work." />

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search projects" class="input max-w-xs">
        <select name="status" class="input max-w-xs">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ProjectStatus::cases() as $item)
                <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <button class="btn-secondary">Filter</button>
    </form>

    <div class="card overflow-hidden">
        @forelse ($projects as $project)
            <a href="{{ route('client.projects.show', $project) }}" class="flex flex-col gap-3 border-b border-ink-100 px-5 py-4 last:border-0 hover:bg-ink-50 dark:border-ink-800 dark:hover:bg-ink-800/40 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">{{ $project->name }}</p>
                    <p class="text-xs text-ink-500">{{ $project->number }}</p>
                    <div class="mt-2 h-2 w-56 max-w-full overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $project->progressPercent() }}%"></div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-badge :tone="$project->health->tone()">{{ $project->health->label() }}</x-badge>
                    <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                    <span class="text-sm text-ink-500">{{ $project->progressPercent() }}%</span>
                </div>
            </a>
        @empty
            <x-empty-state title="No projects yet" description="Your projects will appear here once delivery begins." />
        @endforelse
    </div>
    <div class="mt-4">{{ $projects->links() }}</div>
@endsection
