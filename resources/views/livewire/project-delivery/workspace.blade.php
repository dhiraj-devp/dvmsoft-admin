@php
    $sections = [
        'overview' => 'Overview',
        'tasks' => 'Tasks',
        'deliverables' => 'Deliverables',
        'evidence' => 'Work Evidence',
        'discussion' => 'Discussion',
        'reviews' => 'Reviews',
        'activity' => 'Activity',
    ];
@endphp

<div class="space-y-6">
    <section class="card p-5">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Overall progress</p>
                <p class="mt-1 text-2xl font-semibold">{{ $progress }}%</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $progress }}%"></div>
                </div>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Current stage</p>
                <p class="mt-1 text-lg font-semibold">{{ $current?->name ?: 'Not started' }}</p>
                @if ($current)
                    <x-badge class="mt-1" :tone="$current->status->tone()">{{ $current->status->label() }}</x-badge>
                @endif
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Approval mode</p>
                <p class="mt-1 text-lg font-semibold">{{ $project->client_approval_mode->label() }}</p>
                <p class="mt-1 text-xs text-ink-500">{{ $project->client_approval_mode->description() }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-ink-500">Delivery health</p>
                <p class="mt-1 text-lg font-semibold">{{ $health['label'] }}</p>
                <x-badge class="mt-1" :tone="$health['tone']">{{ $health['label'] }}</x-badge>
            </div>
        </div>
    </section>

    <section class="card p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold">Stage timeline</h2>
            @can('create', App\Models\ProjectStage::class)
                <button type="button" class="btn-primary" wire:click="createStage"><x-icon name="plus" class="h-4 w-4" /> Add stage</button>
            @endcan
        </div>

        @if ($project->stages->isEmpty())
            <x-empty-state title="No delivery stages yet" description="Add ordered stages such as Discovery, Design, Development, QA, and Deployment." />
        @else
            <div class="flex gap-3 overflow-x-auto pb-2">
                @foreach ($project->stages as $item)
                    <button type="button" wire:click="selectStage('{{ $item->id }}')"
                            class="min-w-[11rem] rounded-2xl border px-4 py-3 text-left transition {{ $selectedStageId === $item->id ? 'border-brand-600 bg-brand-50 dark:bg-brand-950' : 'border-ink-200 bg-white hover:border-brand-400 dark:border-ink-700 dark:bg-ink-900' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-lg font-semibold {{ $item->status->tone() === 'success' ? 'text-emerald-600' : ($item->status->tone() === 'danger' ? 'text-red-600' : 'text-brand-700') }}">{{ $item->status->marker() }}</span>
                            @can('create', App\Models\ProjectStage::class)
                                <span class="flex gap-1">
                                    <span wire:click.stop="moveStage('{{ $item->id }}', 'up')" class="text-xs text-ink-400 hover:text-ink-700">↑</span>
                                    <span wire:click.stop="moveStage('{{ $item->id }}', 'down')" class="text-xs text-ink-400 hover:text-ink-700">↓</span>
                                </span>
                            @endcan
                        </div>
                        <p class="mt-1 font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-ink-500">{{ $item->status->label() }} · {{ $item->progressPercent() }}%</p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $item->progressPercent() }}%"></div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    @if ($stage)
        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <div>
                    <h2 class="text-lg font-semibold">{{ $stage->name }}</h2>
                    <p class="text-sm text-ink-500">{{ $stage->description ?: 'No description.' }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-badge :tone="$stage->status->tone()">{{ $stage->status->label() }}</x-badge>
                        @if ($stage->approvalIsRequired())
                            <x-badge tone="warning">Approval required</x-badge>
                        @else
                            <x-badge tone="neutral">Approval not required</x-badge>
                        @endif
                        @if ($stage->isOverdue())
                            <x-badge tone="danger">Overdue</x-badge>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('update', $stage)
                        <button type="button" class="btn-secondary" wire:click="editStage('{{ $stage->id }}')">Edit</button>
                        @if ($stage->status === \App\Enums\StageStatus::NotStarted)
                            <button type="button" class="btn-primary" wire:click="startStage">Start</button>
                        @endif
                        @if ($stage->status === \App\Enums\StageStatus::ChangesRequested)
                            <button type="button" class="btn-primary" wire:click="resumeStage">Resume work</button>
                        @endif
                        @if (in_array($stage->status, [\App\Enums\StageStatus::InProgress, \App\Enums\StageStatus::ChangesRequested], true))
                            @can('submitReview', $stage)
                                <button type="button" class="btn-primary" wire:click="submitStage">Submit for review</button>
                            @endcan
                        @endif
                        @if ($stage->status === \App\Enums\StageStatus::Blocked)
                            <button type="button" class="btn-primary" wire:click="unblockStage">Unblock</button>
                        @elseif ($stage->status !== \App\Enums\StageStatus::Completed)
                            <button type="button" class="btn-secondary" wire:click="blockStage">Block</button>
                        @endif
                    @endcan
                    @can('complete', $stage)
                        @if ($stage->status !== \App\Enums\StageStatus::Completed && in_array($stage->status, [\App\Enums\StageStatus::Approved, \App\Enums\StageStatus::InProgress, \App\Enums\StageStatus::ReadyForReview], true))
                            @if (! $stage->approvalIsRequired() || $stage->status === \App\Enums\StageStatus::Approved || auth()->user()->hasPermission('projects.stage_approval.override'))
                                <button type="button" class="btn-primary" wire:click="completeStage">Complete</button>
                            @endif
                        @endif
                    @endcan
                    @can('delete', $stage)
                        <button type="button" class="text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $stage->id }}', event: 'confirmed-delete-stage', title: 'Delete stage?', message: 'This stage and its delivery records will be archived.' })">Delete</button>
                    @endcan
                </div>
            </div>

            @if (in_array($stage->status, [\App\Enums\StageStatus::InProgress, \App\Enums\StageStatus::ChangesRequested], true))
                @can('submitReview', $stage)
                    <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                        <label class="label">Submission note</label>
                        <textarea wire:model="submitComment" rows="2" class="input" placeholder="Optional note for the client"></textarea>
                        @can('overrideApproval', $stage)
                            <label class="mt-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="submitOverride" class="rounded border-ink-300 text-brand-700">
                                Override incomplete required work
                            </label>
                            @if ($submitOverride)
                                <input type="text" wire:model="overrideReason" class="input mt-2" placeholder="Override reason (required)">
                                @error('override_reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @endif
                        @endcan
                        @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endcan
            @endif

            @can('overrideApproval', $stage)
                @if ($stage->approvalIsRequired() && $stage->status !== \App\Enums\StageStatus::Approved && $stage->status !== \App\Enums\StageStatus::Completed)
                    <div class="border-b border-ink-100 px-5 py-3 text-sm dark:border-ink-800">
                        <label class="label">Complete without approval (override)</label>
                        <input type="text" wire:model="completeOverrideReason" class="input" placeholder="Required if completing before client approval">
                    </div>
                @endif
            @endcan

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
                    <dl class="grid gap-4 sm:grid-cols-3 text-sm">
                        <div><dt class="text-ink-500">Owner</dt><dd class="font-medium">{{ $stage->owner?->name ?: 'Unassigned' }}</dd></div>
                        <div><dt class="text-ink-500">Start</dt><dd class="font-medium">{{ $stage->start_date?->format('d M Y') ?: '—' }}</dd></div>
                        <div><dt class="text-ink-500">Due</dt><dd class="font-medium">{{ $stage->due_date?->format('d M Y') ?: '—' }}</dd></div>
                        <div><dt class="text-ink-500">Progress</dt><dd class="font-medium">{{ $stage->progressPercent() }}%</dd></div>
                        <div><dt class="text-ink-500">Tasks</dt><dd class="font-medium">{{ $stage->tasks->where('status', \App\Enums\TaskStatus::Completed)->count() }}/{{ $stage->tasks->count() }}</dd></div>
                        <div><dt class="text-ink-500">Deliverables</dt><dd class="font-medium">{{ $stage->deliverables->where('status', \App\Enums\StageDeliverableStatus::Completed)->count() }}/{{ $stage->deliverables->count() }}</dd></div>
                    </dl>
                    @if ($stage->notes)
                        <div class="mt-4 rounded-xl bg-ink-50 p-3 text-sm dark:bg-ink-800">
                            <p class="text-xs uppercase tracking-wide text-ink-500">Internal notes</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $stage->notes }}</p>
                        </div>
                    @endif
                    <p class="mt-4 text-xs text-ink-400">Progress = (completed tasks + completed deliverables) ÷ (tasks + deliverables). Stages with neither use 100% only after approval or completion.</p>
                @elseif ($section === 'tasks')
                    <div class="space-y-2">
                        @forelse ($stage->tasks as $task)
                            <div class="flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                                <div>
                                    <p class="font-medium">{{ $task->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $task->assignedUser?->name ?: 'Unassigned' }} · due {{ $task->due_date?->format('d M') ?: '—' }} · {{ $task->estimated_hours ? $task->estimated_hours.'h est' : 'no estimate' }}{{ $task->actual_hours ? ' / '.$task->actual_hours.'h actual' : '' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-badge :tone="$task->priority->tone()">{{ $task->priority->label() }}</x-badge>
                                    <x-badge :tone="$task->status->tone()">{{ $task->status->label() }}</x-badge>
                                </div>
                            </div>
                        @empty
                            <x-empty-state title="No tasks on this stage" description="Assign existing tasks to this stage from the Tasks tab." />
                        @endforelse
                    </div>
                @elseif ($section === 'deliverables')
                    @can('create', App\Models\ProjectStageDeliverable::class)
                        <button type="button" class="btn-secondary mb-4" wire:click="createDeliverable">Add deliverable</button>
                    @endcan
                    @forelse ($stage->deliverables as $deliverable)
                        <div class="mb-2 flex items-center justify-between rounded-xl border border-ink-100 px-3 py-2 text-sm dark:border-ink-800">
                            <label class="flex items-center gap-3">
                                @can('update', $deliverable)
                                    <input type="checkbox" @checked($deliverable->isCompleted()) wire:click="toggleDeliverable('{{ $deliverable->id }}')" class="rounded border-ink-300 text-brand-700">
                                @else
                                    <input type="checkbox" @checked($deliverable->isCompleted()) disabled class="rounded">
                                @endcan
                                <span>
                                    <span class="font-medium">{{ $deliverable->name }}</span>
                                    @if ($deliverable->is_required)<span class="text-xs text-ink-400">required</span>@endif
                                    <span class="block text-xs text-ink-500">{{ $deliverable->description }}</span>
                                </span>
                            </label>
                            @can('delete', $deliverable)
                                <button type="button" class="text-xs text-red-600" @click="$dispatch('confirm', { id: '{{ $deliverable->id }}', event: 'confirmed-delete-deliverable', title: 'Delete deliverable?' })">Delete</button>
                            @endcan
                        </div>
                    @empty
                        <x-empty-state title="No deliverables" />
                    @endforelse
                @elseif ($section === 'evidence')
                    @can('create', App\Models\ProjectStageEvidence::class)
                        <button type="button" class="btn-secondary mb-4" wire:click="$set('showEvidenceForm', true)">Add evidence</button>
                    @endcan
                    @forelse ($stage->evidence as $item)
                        <div class="mb-3 rounded-xl border border-ink-100 p-3 text-sm dark:border-ink-800">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ $item->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $item->type->label() }} · {{ $item->visibility->label() }} · {{ $item->uploadedBy?->name }} · {{ $item->created_at?->format('d M Y H:i') }}</p>
                                    @if ($item->description)<p class="mt-1 text-ink-600">{{ $item->description }}</p>@endif
                                    @if ($item->url)
                                        <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-brand-700">Open link</a>
                                    @endif
                                    @if ($item->hasFile())
                                        <a href="{{ route('projects.stages.evidence.download', [$project, $stage, $item]) }}" class="mt-1 inline-block text-brand-700">Download {{ $item->original_name }}</a>
                                    @endif
                                </div>
                                @can('delete', $item)
                                    <button type="button" class="text-xs text-red-600" @click="$dispatch('confirm', { id: '{{ $item->id }}', event: 'confirmed-delete-evidence', title: 'Remove evidence?' })">Remove</button>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <x-empty-state title="No work evidence yet" />
                    @endforelse
                @elseif ($section === 'discussion')
                    @can('create', App\Models\ProjectStageMessage::class)
                        <form wire:submit="postDiscussion" class="mb-5 space-y-3">
                            <textarea wire:model="discussionBody" rows="3" class="input" placeholder="Write a comment"></textarea>
                            @error('discussionBody') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="discussionInternal" class="rounded border-ink-300 text-brand-700">
                                    Internal only — never shown to the client
                                </label>
                                <input type="file" wire:model="discussionFile" class="text-sm">
                                <button type="submit" class="btn-primary">Post</button>
                            </div>
                        </form>
                    @endcan
                    @forelse ($threads as $message)
                        <div class="mb-4 rounded-xl border {{ $message->is_internal ? 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40' : 'border-ink-100 dark:border-ink-800' }} p-3 text-sm">
                            <p class="text-xs text-ink-500">{{ $message->displayAuthorName() }} · {{ $message->created_at?->diffForHumans() }} @if ($message->is_internal)<span class="font-medium text-amber-700">Internal</span>@endif</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $message->body }}</p>
                            @foreach ($message->attachments as $attachment)
                                <a href="{{ route('projects.stages.attachments.download', [$project, $stage, $message, $attachment]) }}" class="mt-1 block text-brand-700">{{ $attachment->original_name }}</a>
                            @endforeach
                            @foreach ($message->replies as $reply)
                                <div class="mt-3 ml-4 border-l border-ink-200 pl-3">
                                    <p class="text-xs text-ink-500">{{ $reply->displayAuthorName() }} · {{ $reply->created_at?->diffForHumans() }}</p>
                                    <p class="mt-1 whitespace-pre-wrap">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <x-empty-state title="No discussion yet" />
                    @endforelse
                @elseif ($section === 'reviews')
                    @forelse ($stage->submissions as $submission)
                        <div class="mb-3 rounded-xl border border-ink-100 p-3 text-sm dark:border-ink-800">
                            <div class="flex items-center justify-between">
                                <p class="font-medium">Submission #{{ $submission->version }}</p>
                                <x-badge :tone="$submission->status->tone()">{{ $submission->status->label() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-ink-500">Submitted {{ $submission->submitted_at?->format('d M Y H:i') }} @if ($submission->override_incomplete)· Override: {{ $submission->override_reason }}@endif</p>
                            @if ($submission->comment)<p class="mt-1">{{ $submission->comment }}</p>@endif
                            @if ($submission->decided_at)
                                <p class="mt-2 text-xs text-ink-500">Decision by {{ $submission->reviewerName() }} at {{ $submission->decided_at->format('d M Y H:i') }}</p>
                                @if ($submission->decision_comment)<p class="mt-1">{{ $submission->decision_comment }}</p>@endif
                            @endif
                        </div>
                    @empty
                        <x-empty-state title="No review history" />
                    @endforelse
                @else
                    <livewire:crm.timeline :subject-type="App\Models\Project::class" :subject-id="$project->id" :key="'stage-activity-'.$stage->id" />
                @endif
            </div>
        </section>
    @endif

    <div x-show="$wire.showStageForm" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showStageForm = false">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $editingStageId ? 'Edit stage' : 'New stage' }}</h2>
                <button type="button" wire:click="$set('showStageForm', false)"><x-icon name="x" /></button>
            </div>
            <form wire:submit="saveStage" class="space-y-4">
                <div><label class="label">Name</label><input type="text" wire:model="stageForm.name" class="input">@error('stageForm.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="stageForm.description" rows="3" class="input"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Start</label><input type="date" wire:model="stageForm.start_date" class="input"></div>
                    <div><label class="label">Due</label><input type="date" wire:model="stageForm.due_date" class="input"></div>
                </div>
                <div>
                    <label class="label">Owner</label>
                    <select wire:model="stageForm.owner_id" class="input">
                        <option value="">Unassigned</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Team members</label>
                    <select wire:model="stageForm.member_ids" multiple class="input h-32">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Client approval</label>
                    <select wire:model="stageForm.approval_requirement" class="input">
                        @foreach ($approvalRequirements as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="stageForm.client_review_enabled" class="rounded border-ink-300 text-brand-700"> Client review enabled</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="stageForm.requires_evidence" class="rounded border-ink-300 text-brand-700"> Require client-visible evidence before review</label>
                <div><label class="label">Internal notes</label><textarea wire:model="stageForm.notes" rows="2" class="input"></textarea></div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showStageForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save stage</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="$wire.showDeliverableForm" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/40">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showDeliverableForm = false">
            <h2 class="mb-4 text-lg font-semibold">Deliverable</h2>
            <form wire:submit="saveDeliverable" class="space-y-4">
                <div><label class="label">Name</label><input type="text" wire:model="deliverableForm.name" class="input">@error('deliverableForm.name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="deliverableForm.description" rows="2" class="input"></textarea></div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="deliverableForm.is_required" class="rounded border-ink-300 text-brand-700"> Required</label>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showDeliverableForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="$wire.showEvidenceForm" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-ink-950/40">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.showEvidenceForm = false">
            <h2 class="mb-4 text-lg font-semibold">Work evidence</h2>
            <form wire:submit="addEvidence" class="space-y-4">
                <div>
                    <label class="label">Type</label>
                    <select wire:model="evidenceForm.type" class="input">
                        @foreach ($evidenceTypes as $item)
                            <option value="{{ $item->value }}">{{ $item->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">Title</label><input type="text" wire:model="evidenceForm.title" class="input">@error('evidenceForm.title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Description</label><textarea wire:model="evidenceForm.description" rows="2" class="input"></textarea></div>
                <div><label class="label">External URL</label><input type="url" wire:model="evidenceForm.url" class="input" placeholder="https://"><p class="mt-1 text-xs text-ink-400">Links are never downloaded automatically.</p></div>
                <div><label class="label">File</label><input type="file" wire:model="evidenceFile" class="input">@error('evidenceFile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Version</label><input type="text" wire:model="evidenceForm.version" class="input"></div>
                    <div>
                        <label class="label">Visibility</label>
                        <select wire:model="evidenceForm.visibility" class="input">
                            @foreach ($visibilities as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('showEvidenceForm', false)">Cancel</button>
                    <button type="submit" class="btn-primary">Save evidence</button>
                </div>
            </form>
        </div>
    </div>
</div>
