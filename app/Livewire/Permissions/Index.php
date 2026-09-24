<?php

namespace App\Livewire\Permissions;

use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $group = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Permission::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->group, fn ($query) => $query->where('group', $this->group))
            ->orderBy('group')
            ->orderBy('name')
            ->paginate(20);

        $groups = Permission::query()->distinct()->orderBy('group')->pluck('group');

        return view('livewire.permissions.index', compact('permissions', 'groups'));
    }
}
