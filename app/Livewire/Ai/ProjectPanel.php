<?php

namespace App\Livewire\Ai;

use App\Models\Project;
use App\Services\Ai\ProjectAssistant;
use Illuminate\Contracts\View\View;

class ProjectPanel extends AiPanel
{
    public string $projectId = '';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
        $this->authorize($this->permission());
        $this->authorize('view', Project::query()->findOrFail($projectId));
    }

    public function generateSummary(ProjectAssistant $assistant): void
    {
        $project = Project::query()->findOrFail($this->projectId);

        $this->generateWith(fn () => $assistant->analyze($project), $project);
    }

    public function render(): View
    {
        return view('livewire.ai.project-panel');
    }

    protected function feature(): string
    {
        return 'project';
    }

    protected function permission(): string
    {
        return 'ai.projects.use';
    }
}
