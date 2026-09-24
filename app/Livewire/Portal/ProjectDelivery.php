<?php

namespace App\Livewire\Portal;

use App\Enums\StageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\ClientPortal\ClientAccess;
use App\Services\ProjectStageWorkflowService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectDelivery extends Component
{
    use WithFileUploads;

    public string $projectId;

    public ?string $selectedStageId = null;

    public string $section = 'overview';

    public string $reviewComment = '';

    public string $changeReason = '';

    public string $discussionBody = '';

    public $discussionFile = null;

    public function mount(string $projectId, ?string $stageId = null): void
    {
        $this->projectId = $projectId;
        $project = $this->project();
        $this->selectedStageId = $stageId
            ?: $project->currentStage()?->id
            ?: $project->stages()->ordered()->value('id');
    }

    public function selectStage(string $id): void
    {
        $this->stageOrFail($id);
        $this->selectedStageId = $id;
        $this->section = 'overview';
        $this->reviewComment = '';
        $this->changeReason = '';
    }

    public function setSection(string $section): void
    {
        $allowed = ['overview', 'deliverables', 'evidence', 'discussion', 'reviews'];
        $this->section = in_array($section, $allowed, true) ? $section : 'overview';
    }

    public function approve(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->stageOrFail((string) $this->selectedStageId);
        abort_unless($stage->status === StageStatus::ReadyForReview && $stage->client_review_enabled, 403);
        $workflow->approve($stage, $this->portalUser(), $this->reviewComment ?: null);
        $this->reviewComment = '';
        $this->dispatch('notify', type: 'success', message: 'Stage approved.');
    }

    public function requestChanges(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->stageOrFail((string) $this->selectedStageId);
        abort_unless($stage->status === StageStatus::ReadyForReview && $stage->client_review_enabled, 403);
        $this->validate(['changeReason' => ['required', 'string', 'max:4000']]);
        $workflow->requestChanges($stage, $this->portalUser(), $this->changeReason);
        $this->changeReason = '';
        $this->dispatch('notify', type: 'success', message: 'Changes requested.');
    }

    public function postDiscussion(ProjectStageWorkflowService $workflow): void
    {
        $stage = $this->stageOrFail((string) $this->selectedStageId);
        $this->validate([
            'discussionBody' => ['required', 'string', 'max:8000'],
            'discussionFile' => ['nullable', 'file', 'max:20480'],
        ]);
        $workflow->postMessage($stage, [
            'body' => $this->discussionBody,
            'is_internal' => false,
        ], $this->portalUser(), $this->discussionFile);
        $this->discussionBody = '';
        $this->discussionFile = null;
        $this->dispatch('notify', type: 'success', message: 'Comment posted.');
    }

    public function render(ClientAccess $access): View
    {
        $user = $this->portalUser();
        $project = $this->project()->load([
            'stages.deliverables',
            'stages.tasks',
        ]);

        $stage = $this->selectedStageId
            ? $project->stages->firstWhere('id', $this->selectedStageId)
            : $project->stages->first();

        $evidence = collect();
        $threads = collect();
        $submissions = collect();

        if ($stage) {
            $evidence = $access->stageEvidence($user, $project, $stage)->with('uploadedBy')->latest()->get();
            $threads = $access->stageMessages($user, $project, $stage)
                ->whereNull('parent_id')
                ->with(['author', 'clientUser', 'attachments', 'replies' => fn ($query) => $query->visibleToClient()->with(['author', 'clientUser', 'attachments'])])
                ->latest()
                ->get();
            $submissions = $stage->submissions()->with('decidedByClientUser')->orderByDesc('version')->get();
        }

        return view('livewire.portal.project-delivery', [
            'project' => $project,
            'stage' => $stage,
            'progress' => $project->progressPercent(),
            'current' => $project->currentStage(),
            'evidence' => $evidence,
            'threads' => $threads,
            'submissions' => $submissions,
            'clientTasks' => $stage?->tasks ?? collect(),
        ]);
    }

    protected function portalUser()
    {
        return auth('client')->user();
    }

    protected function project(): Project
    {
        return app(ClientAccess::class)->project($this->portalUser(), $this->projectId);
    }

    protected function stageOrFail(string $id): ProjectStage
    {
        return app(ClientAccess::class)->stage($this->portalUser(), $this->project(), $id);
    }
}
