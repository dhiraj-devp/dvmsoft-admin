<?php

namespace App\Livewire\Clients;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Client;
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

    public string $type = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Client::class);
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('clientsPage');
    }

    public function create(): void
    {
        $this->authorize('create', Client::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('update', $client);
        $this->editingId = $client->id;
        $this->form = [
            'type' => $client->type->value,
            'name' => $client->name,
            'email' => $client->email ?? '',
            'phone' => $client->phone ?? '',
            'website' => $client->website ?? '',
            'address' => $client->address ?? '',
            'gst_number' => $client->gst_number ?? '',
            'pan_number' => $client->pan_number ?? '',
            'status' => $client->status->value,
            'account_manager_id' => $client->account_manager_id ?? '',
            'notes' => $client->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(CrmActivityLogger $activities): void
    {
        $client = $this->editingId ? Client::query()->findOrFail($this->editingId) : null;
        $client ? $this->authorize('update', $client) : $this->authorize('create', Client::class);

        $validated = $this->validate([
            'form.type' => ['required', Rule::enum(ClientType::class)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.website' => ['nullable', 'url', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:1000'],
            'form.gst_number' => ['nullable', 'string', 'max:50'],
            'form.pan_number' => ['nullable', 'string', 'max:20'],
            'form.status' => ['required', Rule::enum(ClientStatus::class)],
            'form.account_manager_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = $validated['form'];
        $payload['account_manager_id'] = $payload['account_manager_id'] ?: null;

        if ($client) {
            $client->update($payload);
        } else {
            $client = Client::query()->create($payload);
            $activities->log($client, 'created', 'Client created', $client->name);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Client saved.');
    }

    #[On('confirmed-delete-client')]
    public function delete(string $id): void
    {
        $client = Client::query()->findOrFail($id);
        $this->authorize('delete', $client);
        $client->delete();
        $this->dispatch('notify', type: 'success', message: 'Client deleted.');
    }

    public function render(): View
    {
        $clients = Client::query()
            ->with(['accountManager', 'contacts'])
            ->withCount('contacts')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('gst_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->latest()
            ->paginate(12, pageName: 'clientsPage');

        return view('livewire.clients.index', [
            'clients' => $clients,
            'users' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'statuses' => ClientStatus::cases(),
            'types' => ClientType::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'type' => ClientType::Company->value,
            'name' => '',
            'email' => '',
            'phone' => '',
            'website' => '',
            'address' => '',
            'gst_number' => '',
            'pan_number' => '',
            'status' => ClientStatus::Active->value,
            'account_manager_id' => auth()->id() ?? '',
            'notes' => '',
        ];
        $this->resetValidation();
    }
}
