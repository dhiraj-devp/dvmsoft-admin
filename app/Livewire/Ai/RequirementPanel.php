<?php

namespace App\Livewire\Ai;

use App\Models\Requirement;
use App\Services\Ai\RequirementAssistant;
use Illuminate\Contracts\View\View;

class RequirementPanel extends AiPanel
{
    public string $requirementId = '';

    public bool $canApply = false;

    public function mount(string $requirementId): void
    {
        $this->requirementId = $requirementId;
        $this->authorize($this->permission());
        $requirement = Requirement::query()->findOrFail($requirementId);
        $this->authorize('view', $requirement);
        $this->canApply = request()->user()->can('update', $requirement);
    }

    public function analyzeRequirement(RequirementAssistant $assistant): void
    {
        $requirement = Requirement::query()->findOrFail($this->requirementId);

        $this->generateWith(fn () => $assistant->analyze($requirement), $requirement);
    }

    public function applyAnalysis(RequirementAssistant $assistant): void
    {
        $requirement = Requirement::query()->findOrFail($this->requirementId);
        $this->authorize($this->permission());
        $this->authorize('update', $requirement);

        if (! $this->result) {
            $this->error = 'Generate an analysis before applying it.';

            return;
        }

        $beforeApproval = $requirement->client_approval_status;
        $assistant->apply($requirement, $this->result);
        $requirement->refresh();

        if ($requirement->client_approval_status !== $beforeApproval) {
            $requirement->update(['client_approval_status' => $beforeApproval]);
        }

        $this->applied = true;
        $this->dispatch('notify', type: 'success', message: 'AI analysis applied. Review the requirement and save any further edits.');
    }

    public function render(): View
    {
        return view('livewire.ai.requirement-panel');
    }

    protected function feature(): string
    {
        return 'requirement';
    }

    protected function permission(): string
    {
        return 'ai.requirements.use';
    }
}
