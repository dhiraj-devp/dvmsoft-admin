<?php

namespace App\Livewire\Contacts;

use App\Models\Client;
use App\Models\Contact;
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

    public string $clientId = '';

    public bool $lockedToClient = false;

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [];

    public function mount(?string $clientId = null): void
    {
        $this->authorize('viewAny', Contact::class);
        if ($clientId) {
            $this->clientId = $clientId;
            $this->lockedToClient = true;
        }
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('contactsPage');
    }

    public function create(): void
    {
        $this->authorize('create', Contact::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $contact = Contact::query()->findOrFail($id);
        $this->authorize('update', $contact);
        $this->editingId = $contact->id;
        $this->form = [
            'client_id' => $contact->client_id,
            'name' => $contact->name,
            'email' => $contact->email ?? '',
            'phone' => $contact->phone ?? '',
            'job_title' => $contact->job_title ?? '',
            'is_primary' => $contact->is_primary,
            'notes' => $contact->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $contact = $this->editingId ? Contact::query()->findOrFail($this->editingId) : null;
        $contact ? $this->authorize('update', $contact) : $this->authorize('create', Contact::class);

        $validated = $this->validate([
            'form.client_id' => ['required', 'ulid', 'exists:clients,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.job_title' => ['nullable', 'string', 'max:120'],
            'form.is_primary' => ['boolean'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->lockedToClient) {
            $validated['form']['client_id'] = $this->clientId;
        }

        if ($contact) {
            $contact->update($validated['form']);
        } else {
            Contact::query()->create($validated['form']);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Contact saved.');
    }

    #[On('confirmed-delete-contact')]
    public function delete(string $id): void
    {
        $contact = Contact::query()->findOrFail($id);
        $this->authorize('delete', $contact);
        $contact->delete();
        $this->dispatch('notify', type: 'success', message: 'Contact deleted.');
    }

    public function render(): View
    {
        $contacts = Contact::query()
            ->with('client')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->clientId, fn ($query) => $query->where('client_id', $this->clientId))
            ->latest()
            ->paginate(12, pageName: 'contactsPage');

        return view('livewire.contacts.index', [
            'contacts' => $contacts,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'client_id' => $this->clientId,
            'name' => '',
            'email' => '',
            'phone' => '',
            'job_title' => '',
            'is_primary' => false,
            'notes' => '',
        ];
        $this->resetValidation();
    }
}
