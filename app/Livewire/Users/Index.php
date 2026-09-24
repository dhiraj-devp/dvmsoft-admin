<?php

namespace App\Livewire\Users;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Support\PasswordRules;
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

    public string $roleId = '';

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => '',
        'phone' => '',
        'employee_code' => '',
        'job_title' => '',
        'department_id' => '',
        'manager_id' => '',
        'date_of_joining' => '',
        'is_active' => true,
        'roles' => [],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $user = User::query()->with('roles')->findOrFail($id);
        $this->authorize('update', $user);

        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'phone' => $user->phone ?? '',
            'employee_code' => $user->employee_code ?? '',
            'job_title' => $user->job_title ?? '',
            'department_id' => $user->department_id ?? '',
            'manager_id' => $user->manager_id ?? '',
            'date_of_joining' => optional($user->date_of_joining)?->format('Y-m-d') ?? '',
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('id')->all(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $user = $this->editingId ? User::query()->findOrFail($this->editingId) : null;
        $user ? $this->authorize('update', $user) : $this->authorize('create', User::class);

        $passwordRules = PasswordRules::forUsers();
        if ($user) {
            $passwordRules[0] = 'nullable';
        }

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'form.password' => $passwordRules,
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($this->editingId)],
            'form.job_title' => ['nullable', 'string', 'max:120'],
            'form.department_id' => ['nullable', 'ulid', 'exists:departments,id'],
            'form.manager_id' => ['nullable', 'ulid', 'exists:users,id', Rule::notIn(array_filter([$this->editingId]))],
            'form.date_of_joining' => ['nullable', 'date'],
            'form.is_active' => ['boolean'],
            'form.roles' => ['required', 'array', 'min:1'],
            'form.roles.*' => ['ulid', 'exists:roles,id'],
        ]);

        $payload = collect($validated['form'])->except(['password', 'password_confirmation', 'roles'])->all();
        $payload['department_id'] = $payload['department_id'] ?: null;
        $payload['manager_id'] = $payload['manager_id'] ?: null;
        $payload['date_of_joining'] = $payload['date_of_joining'] ?: null;

        if (filled($validated['form']['password'])) {
            $payload['password'] = $validated['form']['password'];
        }

        if ($user) {
            $user->update($payload);
        } else {
            $user = User::query()->create($payload);
        }

        $user->roles()->sync($validated['form']['roles']);

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'User saved successfully.');
    }

    #[On('confirmed-delete-user')]
    public function delete(string $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();
        $this->dispatch('notify', type: 'success', message: 'User deleted.');
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['roles', 'department'])
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->roleId, fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('roles.id', $this->roleId)))
            ->latest()
            ->paginate(10);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'managers' => User::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'phone' => '',
            'employee_code' => '',
            'job_title' => '',
            'department_id' => '',
            'manager_id' => '',
            'date_of_joining' => '',
            'is_active' => true,
            'roles' => [],
        ];
        $this->resetValidation();
    }
}
