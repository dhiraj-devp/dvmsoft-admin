<?php

namespace App\Livewire\Leave;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?string $employeeId = null;

    public string $search = '';

    public string $status = '';

    public bool $showForm = false;

    public array $form = [];

    public function mount(?string $employeeId = null): void
    {
        $this->authorize('viewAny', LeaveRequest::class);
        $this->employeeId = $employeeId;
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage('leavePage');
    }

    public function create(): void
    {
        $this->authorize('create', LeaveRequest::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(LeaveWorkflowService $workflow): void
    {
        $this->authorize('create', LeaveRequest::class);

        $this->validate([
            'form.employee_id' => ['required', 'ulid', 'exists:employees,id'],
            'form.leave_type_id' => ['required', 'ulid', 'exists:leave_types,id'],
            'form.start_date' => ['required', 'date'],
            'form.end_date' => ['required', 'date', 'after_or_equal:form.start_date'],
            'form.reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::query()->findOrFail($this->form['employee_id']);
        $workflow->request($employee, $this->form, request()->user());

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: 'Leave request submitted.');
    }

    public function approve(string $id, LeaveWorkflowService $workflow): void
    {
        $request = LeaveRequest::query()->findOrFail($id);
        $this->authorize('approve', $request);
        $workflow->approve($request, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Leave approved.');
    }

    public function reject(string $id, LeaveWorkflowService $workflow): void
    {
        $request = LeaveRequest::query()->findOrFail($id);
        $this->authorize('reject', $request);
        $workflow->reject($request, request()->user());
        $this->dispatch('notify', type: 'success', message: 'Leave rejected.');
    }

    public function cancel(string $id, LeaveWorkflowService $workflow): void
    {
        $request = LeaveRequest::query()->findOrFail($id);
        $this->authorize('cancel', $request);
        $workflow->cancel($request);
        $this->dispatch('notify', type: 'success', message: 'Leave cancelled.');
    }

    public function render(): View
    {
        $requests = LeaveRequest::query()
            ->with(['employee.user', 'leaveType', 'approver'])
            ->when($this->employeeId, fn ($query) => $query->where('employee_id', $this->employeeId))
            ->when($this->search, function ($query) {
                $query->whereHas('employee.user', fn ($users) => $users->where('name', 'like', '%'.$this->search.'%'));
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(12, pageName: 'leavePage');

        $balances = $this->employeeId
            ? Employee::query()->with('leaveBalances.leaveType')->find($this->employeeId)?->leaveBalances
            : collect();

        return view('livewire.leave.index', [
            'requests' => $requests,
            'balances' => $balances,
            'employees' => Employee::query()->with('user')->orderBy('id')->get(),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => LeaveRequestStatus::cases(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'employee_id' => $this->employeeId ?? '',
            'leave_type_id' => '',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => '',
        ];
    }
}
