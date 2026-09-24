<?php

namespace App\Livewire\Assets;

use App\Enums\AssetCondition;
use App\Enums\HrChecklistType;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Services\HrChecklistService;
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

    public ?string $employeeId = null;

    public string $search = '';

    public bool $showForm = false;

    public array $form = [];

    public function mount(?string $employeeId = null): void
    {
        $this->authorize('viewAny', EmployeeAsset::class);
        $this->employeeId = $employeeId;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->authorize('create', EmployeeAsset::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(HrChecklistService $checklists): void
    {
        $this->authorize('create', EmployeeAsset::class);

        $this->validate([
            'form.employee_id' => ['required', 'ulid', 'exists:employees,id'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.serial_number' => ['nullable', 'string', 'max:120'],
            'form.assigned_date' => ['required', 'date'],
            'form.condition' => ['required', Rule::enum(AssetCondition::class)],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $asset = EmployeeAsset::query()->create([
            'employee_id' => $this->form['employee_id'],
            'assigned_by_id' => auth()->id(),
            'name' => $this->form['name'],
            'serial_number' => $this->form['serial_number'] ?: null,
            'assigned_date' => $this->form['assigned_date'],
            'condition' => $this->form['condition'],
            'notes' => $this->form['notes'] ?: null,
        ]);

        $checklists->completeItem($asset->employee, HrChecklistType::Onboarding, 'assets_assigned', request()->user());

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Asset assigned.');
    }

    public function markReturned(string $id, HrChecklistService $checklists): void
    {
        $asset = EmployeeAsset::query()->findOrFail($id);
        $this->authorize('return', $asset);
        $asset->update(['return_date' => now()->toDateString()]);

        $outstanding = EmployeeAsset::query()->where('employee_id', $asset->employee_id)->assigned()->count();
        if ($outstanding === 0) {
            $checklists->completeItem($asset->employee, HrChecklistType::Offboarding, 'assets_returned', request()->user());
        }

        $this->dispatch('notify', type: 'success', message: 'Asset marked as returned.');
    }

    #[On('confirmed-delete-asset')]
    public function delete(string $id): void
    {
        $asset = EmployeeAsset::query()->findOrFail($id);
        $this->authorize('delete', $asset);
        $asset->delete();
        $this->dispatch('notify', type: 'success', message: 'Asset removed.');
    }

    public function render(): View
    {
        $assets = EmployeeAsset::query()
            ->with(['employee.user', 'assignedBy'])
            ->when($this->employeeId, fn ($query) => $query->where('employee_id', $this->employeeId))
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('serial_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('employee.user', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest('assigned_date')
            ->paginate(12, pageName: 'assetsPage');

        return view('livewire.assets.index', [
            'assets' => $assets,
            'employees' => Employee::query()->with('user')->get(),
            'conditions' => AssetCondition::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'employee_id' => $this->employeeId ?? '',
            'name' => '',
            'serial_number' => '',
            'assigned_date' => now()->toDateString(),
            'condition' => AssetCondition::Good->value,
            'notes' => '',
        ];
    }
}
