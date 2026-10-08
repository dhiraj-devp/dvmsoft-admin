<?php

namespace App\Livewire\Work;

use App\Enums\WorkLearningStatus;
use App\Models\User;
use App\Models\WorkLearningGoal;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class LearningGoals extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', WorkLearningGoal::class);
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', WorkLearningGoal::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $goal = WorkLearningGoal::query()->findOrFail($id);
        $this->authorize('update', $goal);
        $this->editingId = $goal->id;
        $this->form = [
            'title' => $goal->title,
            'description' => $goal->description ?? '',
            'assigned_user_id' => $goal->assigned_user_id,
            'target_date' => optional($goal->target_date)?->format('Y-m-d') ?? '',
            'status' => $goal->status->value,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $goal = $this->editingId ? WorkLearningGoal::query()->findOrFail($this->editingId) : null;
        $goal ? $this->authorize('update', $goal) : $this->authorize('create', WorkLearningGoal::class);

        $canAssign = auth()->user()->hasPermission('work.goals.manage');

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:5000'],
            'form.assigned_user_id' => [$canAssign ? 'required' : 'nullable', 'ulid', 'exists:users,id'],
            'form.target_date' => ['nullable', 'date'],
            'form.status' => ['required', Rule::enum(WorkLearningStatus::class)],
        ]);

        $payload = $validated['form'];
        $payload['target_date'] = $payload['target_date'] ?: null;

        if (! $canAssign) {
            $payload['assigned_user_id'] = auth()->id();
        }

        if ($goal) {
            $goal->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            WorkLearningGoal::query()->create($payload);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    #[On('confirmed-delete-work-learning-goal')]
    public function delete(string $id): void
    {
        $goal = WorkLearningGoal::query()->findOrFail($id);
        $this->authorize('delete', $goal);
        $goal->delete();
    }

    public function render(): View
    {
        $user = auth()->user();
        $canSeeAll = $user->hasPermission('work.team.view') || $user->hasPermission('work.goals.manage');

        return view('livewire.work.learning-goals', [
            'goals' => WorkLearningGoal::query()
                ->with('assignedUser')
                ->when(! $canSeeAll, fn ($query) => $query->where('assigned_user_id', $user->id))
                ->latest()
                ->paginate(15, ['*'], 'workLearningPage'),
            'statuses' => WorkLearningStatus::cases(),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'canManage' => $user->hasPermission('work.goals.manage'),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'assigned_user_id' => auth()->id() ?? '',
            'target_date' => '',
            'status' => WorkLearningStatus::NotStarted->value,
        ];
    }
}
