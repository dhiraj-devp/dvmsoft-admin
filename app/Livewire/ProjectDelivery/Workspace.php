<?php

namespace App\Livewire\ProjectDelivery;

use App\Enums\StageApprovalRequirement;
use App\Enums\StageEvidenceType;
use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStageDeliverable;
use App\Models\ProjectStageEvidence;
use App\Models\ProjectStageMessage;
use App\Models\User;
use App\Services\ProjectStageWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Workspace extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $projectId;

    public ?string $selectedStageId = null;

    public string $section = 'overview';

    public bool $showStageForm = false;

    public ?string $editingStageId = null;

    public array $stageForm = [];

    public bool $showDeliverableForm = false;

    public ?string $editingDeliverableId = null;

    public array $deliverableForm = [];

    public bool $showEvidenceForm = false;

    public array $evidenceForm = [];

    public $evidenceFile = null;

    public string $discussionBody = '';

    public bool $discussionInternal = false;

    public ?string $discussionParentId = null;

    public $discussionFile = null;

    public string $submitComment = '';

    public bool $submitOverride = false;

    public string $overrideReason = '';

    public string $completeOverrideReason = '';

    public function mount(string $projectId, ?string $stageId = null): void
    {
        $this->projectId = $projectId;
        $project = $this->project();
        $this->authorize('view', $project);

        $this->selectedStageId = $stageId
            ?: $project->currentStage()?->id
            ?: $project->stages()->ordered()->value('id');

        $this->resetStageForm();
        $this->resetDeliverableForm();
        $this->resetEvidenceForm();
    }

    public function selectStage(string $id): void
    {
        $stage = $this->stageOrFail($id);
        $this->authorize('view', $stage);
        $this->selectedStageId = $stage->id;
        $this->section = 'overview';
    }

    public function setSection(string $section): void
    {
        $allowed = ['overview', 'tasks', 'deliverables', 'evidence', 'discussion', 'reviews', 'activity'];
        $this->section = in_array($section, $allowed, true) ? $section : 'overview';
    }

    public function createStage(): void
    {
        $this->authorize('create', ProjectStage::class);
        $this->editingStageId = null;
        $this->resetStageForm();
        $this->showStageForm = true;
    }

    public function editStage(string $id): void
    {
        $stage = $this->stageOrFail($id);
        $this->authorize('update', $stage);
        $this->editingStageId = $stage->id;
        $this->stageForm = [
            'name' => $stage->name,
            'description' => $stage->description ?? '',
            'start_date' => optional($stage->start_date)?->format('Y-m-d') ?? '',
            'due_date' => optional($stage->due_date)?->format('Y-m-d') ?? '',
            'owner_id' => $stage->owner_id ?? '',
            'member_ids' => $stage->members->pluck('id')->all(),
            'client_review_enabled' => $stage->client_review_enabled,
            'approval_requirement' => $stage->approval_requirement->value,
            'requires_evidence' => $stage->requires_evidence,
            'notes' => $stage->notes ?? '',
        ];
        $this->showStageForm = true;
    }

    public function saveStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->editingStageId ? $this->stageOrFail($this->editingStageId) : null;
        $stage ? $this->authorize('update', $stage) : $this->authorize('create', ProjectStage::class);

        $validated = $this->validate([
            'stageForm.name' => ['required', 'string', 'max:255'],
            'stageForm.description' => ['nullable', 'string', 'max:5000'],
            'stageForm.start_date' => ['nullable', 'date'],
            'stageForm.due_date' => ['nullable', 'date'],
            'stageForm.owner_id' => ['nullable', 'ulid', 'exists:users,id'],
            'stageForm.member_ids' => ['array'],
            'stageForm.member_ids.*' => ['ulid', 'exists:users,id'],
            'stageForm.client_review_enabled' => ['boolean'],
            'stageForm.approval_requirement' => ['required', Rule::enum(StageApprovalRequirement::class)],
            'stageForm.requires_evidence' => ['boolean'],
            'stageForm.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = $validated['stageForm'];
        $payload['owner_id'] = $payload['owner_id'] ?: null;
        $payload['start_date'] = $payload['start_date'] ?: null;
        $payload['due_date'] = $payload['due_date'] ?: null;

        if ($stage) {
            $workflow->update($stage, $payload, auth()->user());
        } else {
            $stage = $workflow->create($this->project(), $payload, auth()->user());
            $this->selectedStageId = $stage->id;
        }

        $this->showStageForm = false;
        $this->dispatch('notify', type: 'success', message: 'Stage saved.');
    }

    #[On('confirmed-delete-stage')]
    public function deleteStage(string $id, ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->stageOrFail($id);
        $this->authorize('delete', $stage);
        $workflow->delete($stage, auth()->user());
        if ($this->selectedStageId === $id) {
            $this->selectedStageId = $this->project()->stages()->ordered()->value('id');
        }
        $this->dispatch('notify', type: 'success', message: 'Stage deleted.');
    }

    public function moveStage(string $id, string $direction, ProjectStageWorkflowService $workflow): void
    {
        $this->authorize('create', ProjectStage::class);
        $project = $this->project();
        $ids = $project->stages()->ordered()->pluck('id')->values()->all();
        $index = array_search($id, $ids, true);
        if ($index === false) {
            return;
        }
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($ids[$swap])) {
            return;
        }
        [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];
        $workflow->reorder($project, $ids, auth()->user());
    }

    public function startStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('update', $stage);
        $workflow->start($stage, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Stage started.');
    }

    public function resumeStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('update', $stage);
        $workflow->resume($stage, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Stage resumed.');
    }

    public function submitStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('submitReview', $stage);

        if ($this->submitOverride) {
            $this->authorize('overrideApproval', $stage);
        }

        $workflow->submitForReview(
            $stage,
            auth()->user(),
            $this->submitComment ?: null,
            $this->submitOverride,
            $this->submitOverride ? $this->overrideReason : null,
        );
        $this->submitComment = '';
        $this->submitOverride = false;
        $this->overrideReason = '';
        $this->dispatch('notify', type: 'success', message: 'Stage submitted for review.');
    }

    public function completeStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('complete', $stage);
        $override = $stage->approvalIsRequired() && $stage->status !== StageStatus::Approved;
        if ($override) {
            $this->authorize('overrideApproval', $stage);
        }
        $workflow->complete($stage, auth()->user(), $override, $override ? $this->completeOverrideReason : null);
        $this->completeOverrideReason = '';
        $this->dispatch('notify', type: 'success', message: 'Stage completed.');
    }

    public function blockStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('update', $stage);
        $workflow->block($stage, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Stage blocked.');
    }

    public function unblockStage(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('update', $stage);
        $workflow->unblock($stage, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Stage unblocked.');
    }

    public function createDeliverable(): void
    {
        $this->authorize('create', ProjectStageDeliverable::class);
        $this->editingDeliverableId = null;
        $this->resetDeliverableForm();
        $this->showDeliverableForm = true;
    }

    public function saveDeliverable(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $deliverable = $this->editingDeliverableId
            ? $stage->deliverables()->findOrFail($this->editingDeliverableId)
            : null;
        $deliverable ? $this->authorize('update', $deliverable) : $this->authorize('create', ProjectStageDeliverable::class);

        $validated = $this->validate([
            'deliverableForm.name' => ['required', 'string', 'max:255'],
            'deliverableForm.description' => ['nullable', 'string', 'max:2000'],
            'deliverableForm.is_required' => ['boolean'],
        ]);

        $workflow->saveDeliverable($stage, $validated['deliverableForm'], auth()->user(), $deliverable);
        $this->showDeliverableForm = false;
        $this->dispatch('notify', type: 'success', message: 'Deliverable saved.');
    }

    public function toggleDeliverable(string $id, ProjectStageWorkflowService $workflow): void
    {
        $deliverable = $this->selectedStage()->deliverables()->findOrFail($id);
        $this->authorize('update', $deliverable);
        $workflow->toggleDeliverable($deliverable, auth()->user());
    }

    #[On('confirmed-delete-deliverable')]
    public function deleteDeliverable(string $id, ProjectStageWorkflowService $workflow): void
    {
        $deliverable = $this->selectedStage()->deliverables()->findOrFail($id);
        $this->authorize('delete', $deliverable);
        $workflow->deleteDeliverable($deliverable, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Deliverable deleted.');
    }

    public function addEvidence(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('create', ProjectStageEvidence::class);

        $validated = $this->validate([
            'evidenceForm.type' => ['required', Rule::enum(StageEvidenceType::class)],
            'evidenceForm.title' => ['required', 'string', 'max:255'],
            'evidenceForm.description' => ['nullable', 'string', 'max:4000'],
            'evidenceForm.url' => ['nullable', 'url', 'max:2048'],
            'evidenceForm.version' => ['nullable', 'string', 'max:50'],
            'evidenceForm.visibility' => ['required', Rule::enum(StageEvidenceVisibility::class)],
            'evidenceFile' => ['nullable', 'file', 'max:20480'],
        ]);

        $workflow->addEvidence($stage, $validated['evidenceForm'], auth()->user(), $this->evidenceFile);
        $this->resetEvidenceForm();
        $this->showEvidenceForm = false;
        $this->dispatch('notify', type: 'success', message: 'Evidence added.');
    }

    #[On('confirmed-delete-evidence')]
    public function deleteEvidence(string $id, ProjectStageWorkflowService $workflow): void
    {
        $evidence = $this->selectedStage()->evidence()->findOrFail($id);
        $this->authorize('delete', $evidence);
        $workflow->deleteEvidence($evidence, auth()->user());
        $this->dispatch('notify', type: 'success', message: 'Evidence removed.');
    }

    public function postDiscussion(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->selectedStage();
        $this->authorize('create', ProjectStageMessage::class);

        $validated = $this->validate([
            'discussionBody' => ['required', 'string', 'max:8000'],
            'discussionInternal' => ['boolean'],
            'discussionFile' => ['nullable', 'file', 'max:20480'],
        ]);

        $workflow->postMessage($stage, [
            'body' => $validated['discussionBody'],
            'is_internal' => $this->discussionInternal,
            'parent_id' => $this->discussionParentId,
        ], auth()->user(), $this->discussionFile);

        $this->discussionBody = '';
        $this->discussionInternal = false;
        $this->discussionParentId = null;
        $this->discussionFile = null;
        $this->dispatch('notify', type: 'success', message: 'Comment posted.');
    }

    public function render(): View
    {
        $project = $this->project()->load([
            'stages.owner',
            'stages.members',
            'stages.tasks.assignedUser',
            'stages.deliverables',
            'stages.evidence.uploadedBy',
            'client',
            'manager',
        ]);

        $stage = $this->selectedStageId
            ? $project->stages->firstWhere('id', $this->selectedStageId)
            : $project->stages->first();

        if ($stage) {
            $stage->load([
                'submissions.submittedBy',
                'submissions.decidedByClientUser',
                'submissions.overrideBy',
                'allMessages.author',
                'allMessages.clientUser',
                'allMessages.attachments',
                'allMessages.replies.author',
                'allMessages.replies.clientUser',
                'allMessages.replies.attachments',
            ]);
        }

        return view('livewire.project-delivery.workspace', [
            'project' => $project,
            'stage' => $stage,
            'progress' => $project->progressPercent(),
            'health' => $project->deliveryHealth(),
            'current' => $project->currentStage(),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'approvalRequirements' => StageApprovalRequirement::cases(),
            'evidenceTypes' => StageEvidenceType::cases(),
            'visibilities' => StageEvidenceVisibility::cases(),
            'threads' => $stage
                ? $stage->allMessages->whereNull('parent_id')->sortByDesc('created_at')
                : collect(),
        ]);
    }

    protected function project(): Project
    {
        return Project::query()->findOrFail($this->projectId);
    }

    protected function selectedStage(): ProjectStage
    {
        return $this->stageOrFail((string) $this->selectedStageId);
    }

    protected function stageOrFail(string $id): ProjectStage
    {
        $stage = ProjectStage::query()->with('members')->findOrFail($id);
        abort_unless($stage->project_id === $this->projectId, 404);

        return $stage;
    }

    protected function resetStageForm(): void
    {
        $this->stageForm = [
            'name' => '',
            'description' => '',
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addWeeks(2)->format('Y-m-d'),
            'owner_id' => '',
            'member_ids' => [],
            'client_review_enabled' => true,
            'approval_requirement' => StageApprovalRequirement::Inherit->value,
            'requires_evidence' => false,
            'notes' => '',
        ];
        $this->resetValidation();
    }

    protected function resetDeliverableForm(): void
    {
        $this->deliverableForm = [
            'name' => '',
            'description' => '',
            'is_required' => true,
        ];
    }

    protected function resetEvidenceForm(): void
    {
        $this->evidenceForm = [
            'type' => StageEvidenceType::Url->value,
            'title' => '',
            'description' => '',
            'url' => '',
            'version' => '',
            'visibility' => StageEvidenceVisibility::Client->value,
        ];
        $this->evidenceFile = null;
    }
}
