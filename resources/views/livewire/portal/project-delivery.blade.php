@php
    $sections = [
        'overview' => 'Overview',
        'deliverables' => 'Deliverables',
        'evidence' => 'Work',
        'discussion' => 'Discussion',
        'reviews' => 'Reviews',
    ];
    $waiting = $project->stages->firstWhere('status', \App\Enums\StageStatus::ReadyForReview);
@endphp

<div class="space-y-6">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">Overall progress</p>
            <p class="mt-1 text-2xl font-semibold">{{ $progress }}%</p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">Current stage</p>
            <p class="mt-1 text-lg font-semibold">{{ $current?->name ?: 'Not started' }}</p>
            @if ($current)
                <x-badge class="mt-1" :tone="$current->status->tone()">{{ $current->status->label() }}</x-badge>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">What is waiting for you</p>
            @if ($waiting)
                <p class="mt-1 font-semibold">{{ $waiting->name }}</p>
                <p class="text-xs text-ink-500">Ready for your review</p>
            @else
                <p class="mt-1 text-sm text-ink-500">Nothing needs your review right now.</p>
            @endif
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase tracking-wide text-ink-500">Completed</p>
            <p class="mt-1 text-lg font-semibold">{{ $project->stages->where('status', \App\Enums\StageStatus::Completed)->count() }}/{{ $project->stages->count() }} stages</p>
        </div>
    </section>

    <section class="card p-5">
        <h2 class="mb-4 font-semibold">Delivery timeline</h2>
        @if ($project->stages->isEmpty())
            <x-empty-state title="Delivery stages will appear here" />
        @else
            <div class="flex gap-3 overflow-x-auto pb-2">
                @foreach ($project->stages as $item)
                    <button type="button" wire:click="selectStage('{{ $item->id }}')"
                            class="min-w-[10.5rem] rounded-2xl border px-4 py-3 text-left {{ $selectedStageId === $item->id ? 'border-brand-600 bg-brand-50 dark:bg-brand-950' : 'border-ink-200 dark:border-ink-700' }}">
                        <p class="text-lg font-semibold">{{ $item->status->marker() }}</p>
                        <p class="mt-1 font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-ink-500">{{ $item->status->label() }}</p>
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    @if ($stage)
        <section class="card overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="text-lg font-semibold">{{ $stage->name }}</h2>
                <p class="text-sm text-ink-500">{{ $stage->description }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-badge :tone="$stage->status->tone()">{{ $stage->status->label() }}</x-badge>
                    @if ($stage->due_date)
                        <span class="text-xs text-ink-500">Due {{ $stage->due_date->format(settings('company.date_format', 'd M Y')) }}</span>
                    @endif
                </div>
            </div>

            @if ($stage->status === \App\Enums\StageStatus::ReadyForReview && $stage->client_review_enabled)
                <div class="border-b border-brand-100 bg-brand-50 px-5 py-4 dark:border-brand-900 dark:bg-brand-950/40">
                    <p class="font-medium">This stage is waiting for you</p>
                    <p class="mt-1 text-sm text-ink-600">Review the submitted work, then approve or request changes.</p>
                    <textarea wire:model="reviewComment" rows="2" class="input mt-3" placeholder="Optional approval comment"></textarea>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <button type="button" class="btn-primary" wire:click="approve">Approve</button>
                        <div>
                            <input type="text" wire:model="changeReason" class="input" placeholder="Reason for changes (required)">
                            @error('changeReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <button type="button" class="btn-secondary mt-2 w-full" wire:click="requestChanges">Request changes</button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 border-b border-ink-100 px-5 py-3 dark:border-ink-800">
                @foreach ($sections as $key => $label)
                    <button type="button" wire:click="setSection('{{ $key }}')"
                            class="rounded-full px-3 py-1.5 text-sm {{ $section === $key ? 'bg-brand-700 text-white' : 'bg-ink-50 text-ink-600 dark:bg-ink-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="p-5">
                @if ($section === 'overview')
                    <div class="mb-4 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $stage->progressPercent() }}%"></div>
                    </div>
                    <p class="mb-4 text-sm text-ink-500">{{ $stage->progressPercent() }}% complete</p>
                    <h3 class="mb-2 text-sm font-semibold">Visible tasks</h3>
                    @forelse ($clientTasks as $task)
                        <div class="mb-2 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                            <span>{{ $task->title }}</span>
                            <x-badge :tone="$task->status->tone()">{{ $task->status->label() }}</x-badge>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">No client-visible tasks on this stage.</p>
                    @endforelse
                @elseif ($section === 'deliverables')
                    @forelse ($stage->deliverables as $deliverable)
                        <div class="mb-2 rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                            <p class="font-medium">{{ $deliverable->isCompleted() ? '☑' : '☐' }} {{ $deliverable->name }}</p>
                            <p class="text-xs text-ink-500">{{ $deliverable->description }}</p>
                        </div>
                    @empty
                        <x-empty-state title="No deliverables listed" />
                    @endforelse
                @elseif ($section === 'evidence')
                    @forelse ($evidence as $item)
                        <div class="mb-3 rounded-xl border border-ink-100 p-3 text-sm dark:border-ink-800">
                            <p class="font-medium">{{ $item->title }}</p>
                            <p class="text-xs text-ink-500">{{ $item->type->label() }} · {{ $item->created_at?->format('d M Y') }}</p>
                            @if ($item->description)<p class="mt-1">{{ $item->description }}</p>@endif
                            @if ($item->url)
                                <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-brand-700">Open link</a>
                            @endif
                            @if ($item->hasFile())
                                <a href="{{ route('client.projects.stages.evidence.download', [$project, $stage, $item]) }}" class="mt-1 inline-block text-brand-700">Download {{ $item->original_name }}</a>
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="No work shared yet" />
                    @endforelse
                @elseif ($section === 'discussion')
                    <form wire:submit="postDiscussion" class="mb-5 space-y-3">
                        <textarea wire:model="discussionBody" rows="3" class="input" placeholder="Ask a question or leave feedback"></textarea>
                        @error('discussionBody') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="flex flex-wrap gap-3">
                            <input type="file" wire:model="discussionFile" class="text-sm">
                            <button type="submit" class="btn-primary">Post comment</button>
                        </div>
                    </form>
                    @forelse ($threads as $message)
                        <div class="mb-4 rounded-xl border border-ink-100 p-3 text-sm dark:border-ink-800">
                            <p class="text-xs text-ink-500">{{ $message->displayAuthorName(true) }} · {{ $message->created_at?->diffForHumans() }}</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $message->body }}</p>
                            @foreach ($message->attachments as $attachment)
                                <a href="{{ route('client.projects.stages.attachments.download', [$project, $stage, $message, $attachment]) }}" class="mt-1 block text-brand-700">{{ $attachment->original_name }}</a>
                            @endforeach
                            @foreach ($message->replies as $reply)
                                <div class="mt-3 ml-4 border-l border-ink-200 pl-3">
                                    <p class="text-xs text-ink-500">{{ $reply->displayAuthorName(true) }} · {{ $reply->created_at?->diffForHumans() }}</p>
                                    <p class="mt-1 whitespace-pre-wrap">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <x-empty-state title="No discussion yet" />
                    @endforelse
                @else
                    @forelse ($submissions as $submission)
                        <div class="mb-3 rounded-xl border border-ink-100 p-3 text-sm dark:border-ink-800">
                            <div class="flex items-center justify-between">
                                <p class="font-medium">Submission #{{ $submission->version }}</p>
                                <x-badge :tone="$submission->status->tone()">{{ $submission->status->label() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-ink-500">{{ $submission->submitted_at?->format('d M Y H:i') }}</p>
                            @if ($submission->comment)<p class="mt-1">{{ $submission->comment }}</p>@endif
                            @if ($submission->decided_at)
                                <p class="mt-2 text-xs text-ink-500">{{ $submission->decidedByClientUser?->name ?: 'Reviewed' }} · {{ $submission->decided_at->format('d M Y H:i') }}</p>
                                @if ($submission->decision_comment)<p class="mt-1">{{ $submission->decision_comment }}</p>@endif
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="No reviews yet" />
                    @endforelse
                @endif
            </div>
        </section>
    @endif
</div>
