<?php

namespace App\Livewire\Requirements;

use App\Enums\ProjectPriority;
use App\Enums\RequirementApprovalStatus;
use App\Enums\RequirementStatus;
use App\Models\Project;
use App\Models\Requirement;
use App\Services\CrmActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    public string $projectId = '';

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public $upload = null;

    public function mount(string $projectId): void
    {
        $this->authorize('viewAny', Requirement::class);
        $this->projectId = $projectId;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', Requirement::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $requirement = Requirement::query()->findOrFail($id);
        $this->authorize('update', $requirement);
        $this->editingId = $requirement->id;
        $this->form = [
            'title' => $requirement->title,
            'description' => $requirement->description ?? '',
            'priority' => $requirement->priority->value,
            'status' => $requirement->status->value,
            'client_approval_status' => $requirement->client_approval_status->value,
            'notes' => $requirement->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $requirement = $this->editingId ? Requirement::query()->findOrFail($this->editingId) : null;
        $requirement ? $this->authorize('update', $requirement) : $this->authorize('create', Requirement::class);

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:8000'],
            'form.priority' => ['required', Rule::enum(ProjectPriority::class)],
            'form.status' => ['required', Rule::enum(RequirementStatus::class)],
            'form.client_approval_status' => ['required', Rule::enum(RequirementApprovalStatus::class)],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'upload' => ['nullable', 'file', 'max:10240'],
        ]);

        $payload = $validated['form'];
        $payload['project_id'] = $this->projectId;

        if ($requirement) {
            $requirement->update($payload);
        } else {
            $requirement = Requirement::query()->create($payload);
            $activities->log($requirement->project, 'requirement', 'Requirement added', $requirement->title);
        }

        if ($this->upload instanceof TemporaryUploadedFile) {
            $this->authorize('create', \App\Models\ProjectAttachment::class);
            $path = $this->upload->store('projects/'.$this->projectId.'/requirements', 'local');
            $requirement->attachments()->create([
                'project_id' => $this->projectId,
                'uploaded_by_id' => auth()->id(),
                'original_name' => $this->upload->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $this->upload->getMimeType(),
                'size' => $this->upload->getSize(),
            ]);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Requirement saved.');
    }

    #[On('confirmed-delete-requirement')]
    public function delete(string $id): void
    {
        $requirement = Requirement::query()->findOrFail($id);
        $this->authorize('delete', $requirement);
        $requirement->delete();
        $this->dispatch('notify', type: 'success', message: 'Requirement deleted.');
    }

    public function render(): View
    {
        return view('livewire.requirements.index', [
            'requirements' => Requirement::query()
                ->with('attachments')
                ->where('project_id', $this->projectId)
                ->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(12, pageName: 'requirementsPage'),
            'priorities' => ProjectPriority::cases(),
            'statuses' => RequirementStatus::cases(),
            'approvals' => RequirementApprovalStatus::cases(),
            'project' => Project::query()->findOrFail($this->projectId),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'priority' => ProjectPriority::Medium->value,
            'status' => RequirementStatus::Draft->value,
            'client_approval_status' => RequirementApprovalStatus::Pending->value,
            'notes' => '',
        ];
        $this->upload = null;
        $this->resetValidation();
    }
}
