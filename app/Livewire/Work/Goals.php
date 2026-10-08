<?php

namespace App\Livewire\Work;

use App\Enums\WorkGoalStatus;
use App\Enums\WorkPriority;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkGoal;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Goals extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', WorkGoal::class);
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('workGoalsPage');
    }

    public function create(): void
    {
        $this->authorize('create', WorkGoal::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $goal = WorkGoal::query()->findOrFail($id);
        $this->authorize('update', $goal);
        $this->editingId = $goal->id;
        $this->form = [
            'title' => $goal->title,
            'description' => $goal->description ?? '',
            'expected_outcome' => $goal->expected_outcome ?? '',
            'assigned_user_id' => $goal->assigned_user_id,
            'project_id' => $goal->project_id ?? '',
            'start_date' => optional($goal->start_date)?->format('Y-m-d') ?? '',
            'due_date' => optional($goal->due_date)?->format('Y-m-d') ?? '',
            'priority' => $goal->priority->value,
            'status' => $goal->status->value,
            'progress' => (string) $goal->progress,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $goal = $this->editingId ? WorkGoal::query()->findOrFail($this->editingId) : null;
        $goal ? $this->authorize('update', $goal) : $this->authorize('create', WorkGoal::class);

        $canAssign = auth()->user()->hasPermission('work.goals.manage');

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:5000'],
            'form.expected_outcome' => ['nullable', 'string', 'max:2000'],
            'form.assigned_user_id' => [$canAssign ? 'required' : 'nullable', 'ulid', 'exists:users,id'],
            'form.project_id' => ['nullable', 'ulid', 'exists:projects,id'],
            'form.start_date' => ['nullable', 'date'],
            'form.due_date' => ['nullable', 'date'],
            'form.priority' => ['required', Rule::enum(WorkPriority::class)],
            'form.status' => ['required', Rule::enum(WorkGoalStatus::class)],
            'form.progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $payload = $validated['form'];
        $payload['project_id'] = $payload['project_id'] ?: null;
        $payload['start_date'] = $payload['start_date'] ?: null;
        $payload['due_date'] = $payload['due_date'] ?: null;

        if (! $canAssign) {
            $payload['assigned_user_id'] = auth()->id();
        }

        if ($goal) {
            $goal->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            WorkGoal::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    #[On('confirmed-delete-work-goal')]
    public function delete(string $id): void
    {
        $goal = WorkGoal::query()->findOrFail($id);
        $this->authorize('delete', $goal);
        $goal->delete();
    }

    public function render(): View
    {
        $user = auth()->user();
        $canSeeAll = $user->hasPermission('work.team.view') || $user->hasPermission('work.goals.manage');

        $goals = WorkGoal::query()
            ->with(['assignedUser', 'project'])
            ->when(! $canSeeAll, fn ($query) => $query->where('assigned_user_id', $user->id))
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(20, ['*'], 'workGoalsPage');

        return view('livewire.work.goals', [
            'goals' => $goals,
            'statuses' => WorkGoalStatus::cases(),
            'priorities' => WorkPriority::cases(),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->limit(200)->get(['id', 'name']),
            'canManage' => $user->hasPermission('work.goals.manage'),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'expected_outcome' => '',
            'assigned_user_id' => auth()->id() ?? '',
            'project_id' => '',
            'start_date' => now()->toDateString(),
            'due_date' => '',
            'priority' => WorkPriority::Medium->value,
            'status' => WorkGoalStatus::NotStarted->value,
            'progress' => '0',
        ];
    }
}
