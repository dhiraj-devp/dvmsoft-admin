<?php

namespace App\Livewire\ProjectTeam;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\CrmActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $projectId = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(string $projectId): void
    {
        $this->authorize('viewAny', ProjectMember::class);
        $this->projectId = $projectId;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', ProjectMember::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $member = ProjectMember::query()->findOrFail($id);
        $this->authorize('update', $member);
        $this->editingId = $member->id;
        $this->form = [
            'user_id' => $member->user_id,
            'role' => $member->role,
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $member = $this->editingId ? ProjectMember::query()->findOrFail($this->editingId) : null;
        $member ? $this->authorize('update', $member) : $this->authorize('create', ProjectMember::class);

        $validated = $this->validate([
            'form.user_id' => ['required', 'ulid', 'exists:users,id'],
            'form.role' => ['required', Rule::in(array_keys(config('projects.team_roles')))],
        ]);

        $payload = $validated['form'];
        $payload['project_id'] = $this->projectId;

        $exists = ProjectMember::query()
            ->where('project_id', $this->projectId)
            ->where('user_id', $payload['user_id'])
            ->when($member, fn ($query) => $query->where('id', '!=', $member->id))
            ->exists();

        if ($exists) {
            $this->addError('form.user_id', 'That person is already on this project.');

            return;
        }

        if ($member) {
            $member->update($payload);
        } else {
            $member = ProjectMember::query()->create($payload);
            $activities->log($member->project, 'team', 'Team member added', $member->user?->name.' · '.$member->roleLabel());
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Team member saved.');
    }

    #[On('confirmed-delete-member')]
    public function delete(string $id): void
    {
        $member = ProjectMember::query()->findOrFail($id);
        $this->authorize('delete', $member);
        $member->delete();
        $this->dispatch('notify', type: 'success', message: 'Team member removed.');
    }

    public function render(): View
    {
        return view('livewire.project-team.index', [
            'members' => ProjectMember::query()
                ->with('user')
                ->where('project_id', $this->projectId)
                ->orderBy('role')
                ->get(),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'roles' => config('projects.team_roles'),
            'project' => Project::query()->findOrFail($this->projectId),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'user_id' => '',
            'role' => 'developer',
        ];
        $this->resetValidation();
    }
}
