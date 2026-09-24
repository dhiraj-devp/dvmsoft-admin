<?php

namespace App\Livewire\ChangeRequests;

use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ChangeRequestWorkflowService;
use App\Services\CrmActivityLogger;
use App\Services\SequentialNumberGenerator;
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

    public string $projectId = '';

    public string $search = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(string $projectId): void
    {
        $this->authorize('viewAny', ChangeRequest::class);
        $this->projectId = $projectId;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', ChangeRequest::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $changeRequest = ChangeRequest::query()->findOrFail($id);
        $this->authorize('update', $changeRequest);
        $this->editingId = $changeRequest->id;
        $this->form = [
            'title' => $changeRequest->title,
            'description' => $changeRequest->description ?? '',
            'requested_by_id' => $changeRequest->requested_by_id ?? '',
            'impact_on_cost' => $changeRequest->impact_on_cost,
            'impact_on_timeline_days' => $changeRequest->impact_on_timeline_days,
            'status' => $changeRequest->status->value,
            'notes' => $changeRequest->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(SequentialNumberGenerator $numbers, CrmActivityLogger $activities): void
    {
        $changeRequest = $this->editingId ? ChangeRequest::query()->findOrFail($this->editingId) : null;
        $changeRequest ? $this->authorize('update', $changeRequest) : $this->authorize('create', ChangeRequest::class);

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:8000'],
            'form.requested_by_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.impact_on_cost' => ['nullable', 'numeric'],
            'form.impact_on_timeline_days' => ['nullable', 'integer', 'min:0'],
            'form.status' => ['required', Rule::enum(ChangeRequestStatus::class)],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $validated['form'];
        $payload['project_id'] = $this->projectId;
        $payload['requested_by_id'] = $payload['requested_by_id'] ?: auth()->id();
        $payload['impact_on_cost'] = $payload['impact_on_cost'] === '' ? null : $payload['impact_on_cost'];
        $payload['impact_on_timeline_days'] = $payload['impact_on_timeline_days'] === '' ? null : $payload['impact_on_timeline_days'];

        if ($changeRequest) {
            $changeRequest->update($payload);
        } else {
            $payload['number'] = $numbers->nextChangeRequest();
            $payload['status'] = ChangeRequestStatus::Pending->value;
            $changeRequest = ChangeRequest::query()->create($payload);
            $activities->log($changeRequest->project, 'change_request', 'Change request opened', $changeRequest->number.' · '.$changeRequest->title);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Change request saved.');
    }

    public function approve(string $id, ChangeRequestWorkflowService $workflow): void
    {
        $changeRequest = ChangeRequest::query()->findOrFail($id);
        $this->authorize('approve', $changeRequest);
        $workflow->approve($changeRequest, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Change request approved.');
    }

    public function reject(string $id, ChangeRequestWorkflowService $workflow): void
    {
        $changeRequest = ChangeRequest::query()->findOrFail($id);
        $this->authorize('approve', $changeRequest);
        $workflow->reject($changeRequest, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Change request rejected.');
    }

    public function implement(string $id, ChangeRequestWorkflowService $workflow): void
    {
        $changeRequest = ChangeRequest::query()->findOrFail($id);
        $this->authorize('implement', $changeRequest);
        $workflow->implement($changeRequest, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Change request marked implemented.');
    }

    #[On('confirmed-delete-change-request')]
    public function delete(string $id): void
    {
        $changeRequest = ChangeRequest::query()->findOrFail($id);
        $this->authorize('delete', $changeRequest);
        $changeRequest->delete();
        $this->dispatch('notify', type: 'success', message: 'Change request deleted.');
    }

    public function render(): View
    {
        return view('livewire.change-requests.index', [
            'changeRequests' => ChangeRequest::query()
                ->with(['requestedBy', 'clientUser', 'decidedBy'])
                ->where('project_id', $this->projectId)
                ->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->latest()
                ->paginate(12, pageName: 'changeRequestsPage'),
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => ChangeRequestStatus::cases(),
            'project' => Project::query()->findOrFail($this->projectId),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'description' => '',
            'requested_by_id' => auth()->id() ?? '',
            'impact_on_cost' => '',
            'impact_on_timeline_days' => '',
            'status' => ChangeRequestStatus::Pending->value,
            'notes' => '',
        ];
        $this->resetValidation();
    }
}
