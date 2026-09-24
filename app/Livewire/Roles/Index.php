<?php

namespace App\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
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

    public bool $showForm = false;

    public ?string $editingId = null;

    public array $form = [
        'name' => '',
        'description' => '',
        'permissions' => [],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function create(): void
    {
        $this->authorize('create', Role::class);
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $role = Role::query()->with('permissions')->findOrFail($id);
        $this->authorize('update', $role);
        $this->editingId = $role->id;
        $this->form = [
            'name' => $role->name,
            'description' => $role->description ?? '',
            'permissions' => $role->permissions->pluck('id')->all(),
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $role = $this->editingId ? Role::query()->findOrFail($this->editingId) : null;
        $role ? $this->authorize('update', $role) : $this->authorize('create', Role::class);

        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.permissions' => ['required', 'array', 'min:1'],
            'form.permissions.*' => ['ulid', 'exists:permissions,id'],
        ]);

        $payload = [
            'name' => $validated['form']['name'],
            'description' => $validated['form']['description'] ?? null,
        ];

        if ($role) {
            $role->update($payload);
        } else {
            $role = Role::query()->create($payload);
        }

        $role->permissions()->sync($validated['form']['permissions']);
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Role saved successfully.');
    }

    #[On('confirmed-delete-role')]
    public function delete(string $id): void
    {
        $role = Role::query()->findOrFail($id);
        $this->authorize('delete', $role);
        $role->delete();
        $this->dispatch('notify', type: 'success', message: 'Role deleted.');
    }

    public function render(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(12);

        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');

        return view('livewire.roles.index', compact('roles', 'permissions'));
    }

    protected function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'description' => '',
            'permissions' => [],
        ];
        $this->resetValidation();
    }
}
