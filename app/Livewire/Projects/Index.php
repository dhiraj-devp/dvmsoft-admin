<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $health = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('projectsPage');
    }

    #[On('confirmed-delete-project')]
    public function delete(string $id): void
    {
        $project = Project::query()->findOrFail($id);
        $this->authorize('delete', $project);
        $project->delete();
        $this->dispatch('notify', type: 'success', message: 'Project archived.');
    }

    public function render(): View
    {
        $projects = Project::query()
            ->with(['client', 'manager'])
            ->withCount(['tasks', 'members'])
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->health, fn ($query) => $query->where('health', $this->health))
            ->latest()
            ->paginate(12, pageName: 'projectsPage');

        return view('livewire.projects.index', [
            'projects' => $projects,
            'statuses' => ProjectStatus::cases(),
            'healths' => ProjectHealth::cases(),
            'priorities' => ProjectPriority::cases(),
        ]);
    }
}
