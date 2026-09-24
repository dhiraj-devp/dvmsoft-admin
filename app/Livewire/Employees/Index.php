<?php

namespace App\Livewire\Employees;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
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

    public string $status = '';

    public string $departmentId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Employee::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage('employeesPage');
    }

    #[On('confirmed-delete-employee')]
    public function delete(string $id): void
    {
        $employee = Employee::query()->findOrFail($id);
        $this->authorize('delete', $employee);
        $employee->user?->update(['is_active' => false]);
        $employee->delete();
        $this->dispatch('notify', type: 'success', message: 'Employee record removed.');
    }

    public function render(): View
    {
        $employees = Employee::query()
            ->with(['user.department', 'user.manager'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($users) {
                    $users->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('employee_code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, fn ($query) => $query->where('employment_status', $this->status))
            ->when($this->departmentId, fn ($query) => $query->whereHas('user', fn ($users) => $users->where('department_id', $this->departmentId)))
            ->latest()
            ->paginate(12, pageName: 'employeesPage');

        return view('livewire.employees.index', [
            'employees' => $employees,
            'statuses' => EmploymentStatus::cases(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
