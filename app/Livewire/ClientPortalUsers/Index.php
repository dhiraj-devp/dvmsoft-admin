<?php

namespace App\Livewire\ClientPortalUsers;

use App\Models\Client;
use App\Models\ClientUser;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $clientId = '';

    public bool $showForm = false;

    public array $form = [];

    public function mount(string $clientId): void
    {
        $this->clientId = $clientId;
        $this->authorize('managePortal', $this->client());
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('managePortal', $this->client());
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('managePortal', $this->client());

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('client_users', 'email')],
            'form.password' => ['required', PasswordRule::min(8)],
            'form.is_active' => ['boolean'],
        ]);

        $user = ClientUser::query()->create([
            'client_id' => $this->clientId,
            'name' => $validated['form']['name'],
            'email' => $validated['form']['email'],
            'password' => $validated['form']['password'],
            'is_active' => (bool) ($validated['form']['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Portal user created for '.$user->email.'.');
    }

    public function toggleActive(string $id): void
    {
        $this->authorize('managePortal', $this->client());
        $user = $this->ownedUser($id);
        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('notify', type: 'success', message: $user->is_active ? 'Portal user activated.' : 'Portal user deactivated.');
    }

    public function sendReset(string $id): void
    {
        $this->authorize('managePortal', $this->client());
        $user = $this->ownedUser($id);
        Password::broker('client_users')->sendResetLink(['email' => $user->email]);
        $this->dispatch('notify', type: 'success', message: 'Password reset email queued.');
    }

    #[On('confirmed-delete-portal-user')]
    public function delete(string $id): void
    {
        $this->authorize('managePortal', $this->client());
        $this->ownedUser($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Portal user removed.');
    }

    public function render(): View
    {
        return view('livewire.client-portal-users.index', [
            'users' => ClientUser::query()
                ->where('client_id', $this->clientId)
                ->latest()
                ->get(),
        ]);
    }

    protected function client(): Client
    {
        return Client::query()->findOrFail($this->clientId);
    }

    protected function ownedUser(string $id): ClientUser
    {
        return ClientUser::query()
            ->where('client_id', $this->clientId)
            ->findOrFail($id);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'is_active' => true,
        ];
        $this->resetValidation();
    }
}
