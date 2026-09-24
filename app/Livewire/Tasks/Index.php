<?php

namespace App\Livewire\Tasks;

use App\Enums\ProjectPriority;
use App\Enums\TaskStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\User;
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

    public bool $mineOnly = false;

    public bool $lockedToProject = false;

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(?string $projectId = null, bool $mineOnly = false): void
    {
        $this->authorize('viewAny', Task::class);
        $this->mineOnly = $mineOnly;
        if ($projectId) {
            $this->projectId = $projectId;
            $this->lockedToProject = true;
        }
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('tasksPage');
    }

    public function create(): void
    {
        $this->authorize('create', Task::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $task = Task::query()->findOrFail($id);
        $this->authorize('update', $task);
        $this->editingId = $task->id;
        $this->form = [
            'project_id' => $task->project_id,
            'milestone_id' => $task->milestone_id ?? '',
            'stage_id' => $task->stage_id ?? '',
            'assigned_user_id' => $task->assigned_user_id ?? '',
            'title' => $task->title,
            'description' => $task->description ?? '',
            'priority' => $task->priority->value,
            'status' => $task->status->value,
            'start_date' => optional($task->start_date)?->format('Y-m-d') ?? '',
            'due_date' => optional($task->due_date)?->format('Y-m-d') ?? '',
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'notes' => $task->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $task = $this->editingId ? Task::query()->findOrFail($this->editingId) : null;
        $task ? $this->authorize('update', $task) : $this->authorize('create', Task::class);

        $validated = $this->validate([
            'form.project_id' => ['required', 'ulid', 'exists:projects,id'],
            'form.milestone_id' => ['nullable', 'ulid', 'exists:milestones,id'],
            'form.stage_id' => ['nullable', 'ulid', 'exists:project_stages,id'],
            'form.assigned_user_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:5000'],
            'form.priority' => ['required', Rule::enum(ProjectPriority::class)],
            'form.status' => ['required', Rule::enum(TaskStatus::class)],
            'form.start_date' => ['nullable', 'date'],
            'form.due_date' => ['nullable', 'date'],
            'form.estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'form.actual_hours' => ['nullable', 'numeric', 'min:0'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $validated['form'];
        if ($this->lockedToProject) {
            $payload['project_id'] = $this->projectId;
        }
        $payload['milestone_id'] = $payload['milestone_id'] ?: null;
        $payload['stage_id'] = $payload['stage_id'] ?: null;
        $payload['assigned_user_id'] = $payload['assigned_user_id'] ?: null;
        $payload['estimated_hours'] = $payload['estimated_hours'] === '' ? null : $payload['estimated_hours'];
        $payload['actual_hours'] = $payload['actual_hours'] === '' ? null : $payload['actual_hours'];
        $payload['start_date'] = $payload['start_date'] ?: null;
        $payload['due_date'] = $payload['due_date'] ?: null;

        if ($payload['milestone_id']) {
            $milestone = Milestone::query()->find($payload['milestone_id']);
            if (! $milestone || $milestone->project_id !== $payload['project_id']) {
                $this->addError('form.milestone_id', 'Choose a milestone from this project.');

                return;
            }
        }

        if ($payload['stage_id']) {
            $stage = ProjectStage::query()->find($payload['stage_id']);
            if (! $stage || $stage->project_id !== $payload['project_id']) {
                $this->addError('form.stage_id', 'Choose a stage from this project.');

                return;
            }
        }

        if ($task) {
            $previous = $task->status->value;
            $task->update($payload);
            if ($previous !== $task->fresh()->status->value) {
                $activities->log($task->project, 'task', 'Task status changed', $task->title.': '.$previous.' → '.$task->status->label());
            }
        } else {
            $task = Task::query()->create($payload);
            $activities->log($task->project, 'task', 'Task created', $task->title);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Task saved.');
    }

    #[On('confirmed-delete-task')]
    public function delete(string $id): void
    {
        $task = Task::query()->findOrFail($id);
        $this->authorize('delete', $task);
        $task->delete();
        $this->dispatch('notify', type: 'success', message: 'Task deleted.');
    }

    public function render(): View
    {
        $tasks = Task::query()
            ->with(['project', 'assignedUser', 'milestone', 'stage'])
            ->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->when($this->mineOnly, fn ($query) => $query->where('assigned_user_id', auth()->id()))
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->paginate(12, pageName: 'tasksPage');

        $projectId = $this->form['project_id'] ?: $this->projectId;

        return view('livewire.tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::query()->orderBy('name')->limit(200)->get(['id', 'name', 'number']),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'milestones' => Milestone::query()
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
                ->orderBy('name')
                ->get(['id', 'name', 'project_id']),
            'stages' => ProjectStage::query()
                ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
                ->orderBy('sequence')
                ->get(['id', 'name', 'project_id']),
            'statuses' => TaskStatus::cases(),
            'priorities' => ProjectPriority::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'project_id' => $this->projectId ?: '',
            'milestone_id' => '',
            'stage_id' => '',
            'assigned_user_id' => $this->mineOnly ? (auth()->id() ?? '') : '',
            'title' => '',
            'description' => '',
            'priority' => ProjectPriority::Medium->value,
            'status' => TaskStatus::Todo->value,
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'estimated_hours' => '',
            'actual_hours' => '',
            'notes' => '',
        ];
        $this->resetValidation();
    }
}
