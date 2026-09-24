<?php

namespace App\Livewire\Milestones;

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\CrmActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?string $projectId = null;

    public bool $lockedToProject = false;

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(?string $projectId = null): void
    {
        $this->authorize('viewAny', Milestone::class);
        if ($projectId) {
            $this->projectId = $projectId;
            $this->lockedToProject = true;
        }
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('milestonesPage');
    }

    public function create(): void
    {
        $this->authorize('create', Milestone::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $milestone = Milestone::query()->findOrFail($id);
        $this->authorize('update', $milestone);
        $this->editingId = $milestone->id;
        $this->form = [
            'project_id' => $milestone->project_id,
            'name' => $milestone->name,
            'description' => $milestone->description ?? '',
            'due_date' => optional($milestone->due_date)?->format('Y-m-d') ?? '',
            'status' => $milestone->status->value,
            'completion_percentage' => $milestone->completion_percentage,
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $milestone = $this->editingId ? Milestone::query()->findOrFail($this->editingId) : null;
        $milestone ? $this->authorize('update', $milestone) : $this->authorize('create', Milestone::class);

        $validated = $this->validate([
            'form.project_id' => ['required', 'ulid', 'exists:projects,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:5000'],
            'form.due_date' => ['nullable', 'date'],
            'form.status' => ['required', Rule::enum(MilestoneStatus::class)],
            'form.completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $payload = $validated['form'];
        if ($this->lockedToProject) {
            $payload['project_id'] = $this->projectId;
        }
        $payload['due_date'] = $payload['due_date'] ?: null;
        if ($payload['status'] === MilestoneStatus::Completed->value) {
            $payload['completion_percentage'] = 100;
            $payload['completed_at'] = now();
        }

        if ($milestone) {
            $milestone->update($payload);
        } else {
            $milestone = Milestone::query()->create($payload);
            $activities->log($milestone->project, 'milestone', 'Milestone created', $milestone->name);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Milestone saved.');
    }

    #[On('confirmed-delete-milestone')]
    public function delete(string $id): void
    {
        $milestone = Milestone::query()->findOrFail($id);
        $this->authorize('delete', $milestone);
        $milestone->delete();
        $this->dispatch('notify', type: 'success', message: 'Milestone deleted.');
    }

    public function render(): View
    {
        $milestones = Milestone::query()
            ->with('project')
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->orderBy('due_date')
            ->paginate(12, pageName: 'milestonesPage');

        return view('livewire.milestones.index', [
            'milestones' => $milestones,
            'projects' => Project::query()->orderBy('name')->limit(200)->get(['id', 'name', 'number']),
            'statuses' => MilestoneStatus::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'project_id' => $this->projectId ?: '',
            'name' => '',
            'description' => '',
            'due_date' => now()->addWeeks(2)->format('Y-m-d'),
            'status' => MilestoneStatus::Pending->value,
            'completion_percentage' => 0,
        ];
        $this->resetValidation();
    }
}
