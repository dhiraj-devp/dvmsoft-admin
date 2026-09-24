<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $typeId = '';

    #[Url]
    public string $ownerId = '';

    #[Url]
    public string $clientId = '';

    #[Url]
    public string $projectId = '';

    #[Url]
    public string $employeeId = '';

    #[Url]
    public string $expiry = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('documentsPage');
    }

    public function updatingStatus(): void
    {
        $this->resetPage('documentsPage');
    }

    #[On('confirmed-delete-document')]
    public function delete(string $id): void
    {
        $document = Document::query()->findOrFail($id);
        $this->authorize('delete', $document);
        $document->delete();
        $this->dispatch('notify', type: 'success', message: 'Document removed.');
    }

    public function render(): View
    {
        $warning = (int) settings('documents.expiry_warning_days', 30);

        $documents = Document::query()
            ->with(['type', 'owner', 'client', 'project', 'employee.user'])
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('project', fn ($projects) => $projects->where('name', 'like', '%'.$this->search.'%')->orWhere('number', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('employee.user', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->typeId, fn ($query) => $query->where('document_type_id', $this->typeId))
            ->when($this->ownerId, fn ($query) => $query->where('owner_id', $this->ownerId))
            ->when($this->clientId, fn ($query) => $query->where('client_id', $this->clientId))
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->when($this->employeeId, fn ($query) => $query->where('employee_id', $this->employeeId))
            ->when($this->expiry === 'expiring', fn ($query) => $query->expiring($warning)->whereDate('expiry_date', '>=', now()->toDateString()))
            ->when($this->expiry === 'expired', fn ($query) => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now()->toDateString())->where('status', '!=', DocumentStatus::Archived->value))
            ->when($this->expiry === 'has_expiry', fn ($query) => $query->whereNotNull('expiry_date'))
            ->latest()
            ->paginate(12, pageName: 'documentsPage');

        return view('livewire.documents.index', [
            'documents' => $documents,
            'statuses' => DocumentStatus::cases(),
            'types' => DocumentType::query()->orderBy('sort_order')->orderBy('name')->get(),
            'owners' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
