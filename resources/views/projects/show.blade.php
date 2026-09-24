@php
    $tabs = [
        'overview' => 'Overview',
        'delivery' => 'Delivery',
        'tasks' => 'Tasks',
        'milestones' => 'Milestones',
        'requirements' => 'Requirements',
        'change-requests' => 'Change Requests',
        'team' => 'Team',
        'documents' => 'Documents',
        'activity' => 'Activity',
    ];
    $progress = $project->progressPercent();
@endphp

@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $project->name }}" description="{{ $project->number }} · {{ $project->client?->name }}" :breadcrumbs="['Projects' => route('projects.dashboard'), 'All Projects' => route('projects.index'), $project->name => null]">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @can('create', App\Models\Invoice::class)
                    <form method="POST" action="{{ route('projects.invoice', $project) }}">@csrf<button class="btn-primary">Create invoice</button></form>
                @endcan
                @can('update', $project)
                    <a href="{{ route('projects.edit', $project) }}" class="btn-secondary">Edit project</a>
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap gap-2 overflow-x-auto">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('projects.show', ['project' => $project, 'tab' => $key]) }}"
               class="rounded-full px-4 py-2 text-sm {{ $tab === $key ? 'bg-brand-700 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50 dark:bg-ink-900 dark:text-ink-300 dark:ring-ink-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <section class="card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-ink-500">{{ $project->description ?: 'No description yet.' }}</p>
                        </div>
                        <div class="flex gap-2">
                            <x-badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-badge>
                            <x-badge :tone="$project->health->tone()">{{ $project->health->label() }}</x-badge>
                            <x-badge :tone="$project->priority->tone()">{{ $project->priority->label() }}</x-badge>
                        </div>
                    </div>
                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-ink-500">Progress</span>
                            <span class="font-medium">{{ $progress }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="text-ink-500">Budget</dt>
                            <dd class="font-medium">{{ $project->budget ? money($project->budget) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Manager</dt>
                            <dd class="font-medium">{{ $project->manager?->name ?: 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Client</dt>
                            <dd class="font-medium"><a class="text-brand-700" href="{{ route('clients.show', $project->client) }}">{{ $project->client?->name }}</a></dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Quotation</dt>
                            <dd class="font-medium">
                                @if ($project->quotation)
                                    <a class="text-brand-700" href="{{ route('quotations.show', $project->quotation) }}">{{ $project->quotation->number }}</a>
                                @else
                                    Direct project
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Start</dt>
                            <dd class="font-medium">{{ $project->start_date?->format('d M Y') ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">Expected end</dt>
                            <dd class="font-medium">{{ $project->expected_end_date?->format('d M Y') ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                @can('viewAny', App\Models\Invoice::class)
                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Invoices</h2>
                    @forelse ($project->invoices as $invoice)
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-brand-700">{{ $invoice->number }}</a>
                            <span class="flex items-center gap-2">
                                <span>{{ money($invoice->total) }}</span>
                                <x-badge :tone="$invoice->status->tone()">{{ $invoice->status->label() }}</x-badge>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">No invoices linked to this project.</p>
                    @endforelse
                </section>
                @endcan

                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Milestones</h2>
                    @forelse ($project->milestones as $milestone)
                        <div class="mb-3 flex items-center justify-between text-sm">
                            <span>{{ $milestone->name }}</span>
                            <span class="flex items-center gap-2">
                                <span class="text-ink-500">{{ $milestone->completion_percentage }}%</span>
                                <x-badge :tone="$milestone->status->tone()">{{ $milestone->status->label() }}</x-badge>
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">No milestones yet.</p>
                    @endforelse
                </section>

                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Upcoming deadlines</h2>
                    @php
                        $deadlines = $project->tasks
                            ->filter(fn ($task) => $task->status !== \App\Enums\TaskStatus::Completed && $task->due_date)
                            ->sortBy('due_date')
                            ->take(6);
                    @endphp
                    @forelse ($deadlines as $task)
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span>{{ $task->title }}</span>
                            <span class="text-ink-500">{{ $task->due_date?->format('d M') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">No open deadlines.</p>
                    @endforelse
                </section>
            </div>
            <div class="space-y-6">
                @can('ai.projects.use')
                    <livewire:ai.project-panel :project-id="$project->id" :key="'project-ai-'.$project->id" />
                @endcan
                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Team</h2>
                    @forelse ($project->members as $member)
                        <p class="mb-2 text-sm">{{ $member->user?->name }} <span class="text-ink-500">· {{ $member->roleLabel() }}</span></p>
                    @empty
                        <p class="text-sm text-ink-500">No team assigned.</p>
                    @endforelse
                </section>
                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Open change requests</h2>
                    @forelse ($project->changeRequests->filter(fn ($item) => $item->status === \App\Enums\ChangeRequestStatus::Pending) as $changeRequest)
                        <p class="mb-2 text-sm">{{ $changeRequest->number }} · {{ $changeRequest->title }}</p>
                    @empty
                        <p class="text-sm text-ink-500">None pending.</p>
                    @endforelse
                </section>
                <section class="card p-5">
                    <h2 class="mb-4 font-semibold">Recent activity</h2>
                    <livewire:crm.timeline :subject-type="App\Models\Project::class" :subject-id="$project->id" :key="'overview-activity-'.$project->id" />
                </section>
            </div>
        </div>
    @elseif ($tab === 'delivery')
        <livewire:project-delivery.workspace :project-id="$project->id" :key="'project-delivery-'.$project->id" />
    @elseif ($tab === 'tasks')
        <livewire:tasks.index :project-id="$project->id" :key="'project-tasks-'.$project->id" />
    @elseif ($tab === 'milestones')
        <livewire:milestones.index :project-id="$project->id" :key="'project-milestones-'.$project->id" />
    @elseif ($tab === 'requirements')
        <livewire:requirements.index :project-id="$project->id" :key="'project-requirements-'.$project->id" />
    @elseif ($tab === 'change-requests')
        <livewire:change-requests.index :project-id="$project->id" :key="'project-crs-'.$project->id" />
    @elseif ($tab === 'team')
        <livewire:project-team.index :project-id="$project->id" :key="'project-team-'.$project->id" />
    @elseif ($tab === 'documents')
        <livewire:project-files.index :project-id="$project->id" :key="'project-files-'.$project->id" />
    @else
        <section class="card p-5">
            <livewire:crm.timeline :subject-type="App\Models\Project::class" :subject-id="$project->id" :key="'project-activity-'.$project->id" />
        </section>
    @endif
@endsection
