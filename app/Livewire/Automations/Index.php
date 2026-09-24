<?php

namespace App\Livewire\Automations;

use App\Models\Automation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $module = '';

    public string $enabled = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Automation::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('automationsPage');
    }

    public function toggle(string $id): void
    {
        $automation = Automation::query()->findOrFail($id);
        $this->authorize('update', $automation);

        $automation->update(['enabled' => ! $automation->enabled]);

        $this->dispatch('notify', type: 'success', message: $automation->enabled ? 'Automation enabled.' : 'Automation disabled.');
    }

    public function render(): View
    {
        $automations = Automation::query()
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('key', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->module !== '', fn ($query) => $query->where('module', $this->module))
            ->when($this->enabled === '1', fn ($query) => $query->where('enabled', true))
            ->when($this->enabled === '0', fn ($query) => $query->where('enabled', false))
            ->orderBy('module')
            ->orderBy('name')
            ->paginate(12, pageName: 'automationsPage');

        $modules = Automation::query()->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('livewire.automations.index', [
            'automations' => $automations,
            'modules' => $modules,
        ]);
    }
}
