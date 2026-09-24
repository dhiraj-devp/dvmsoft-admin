<?php

namespace App\Livewire\Employees;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\ProbationStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeProvisioningService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?string $employeeId = null;

    public array $form = [];

    public $photo = null;

    public function mount(?Employee $employee = null): void
    {
        if ($employee?->exists) {
            $this->authorize('update', $employee);
            $this->employeeId = $employee->id;
            $user = $employee->user;
            $this->form = [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'phone' => $user?->phone ?? '',
                'job_title' => $user?->job_title ?? '',
                'department_id' => $user?->department_id ?? '',
                'manager_id' => $user?->manager_id ?? '',
                'date_of_joining' => optional($user?->date_of_joining)?->format('Y-m-d') ?? '',
                'employee_code' => $user?->employee_code ?? '',
                'employment_type' => $employee->employment_type->value,
                'employment_status' => $employee->employment_status->value,
                'probation_days' => $employee->probation_days,
                'probation_status' => $employee->probation_status->value,
                'probation_end_date' => optional($employee->probation_end_date)?->format('Y-m-d') ?? '',
                'confirmation_date' => optional($employee->confirmation_date)?->format('Y-m-d') ?? '',
                'exit_date' => optional($employee->exit_date)?->format('Y-m-d') ?? '',
                'exit_reason' => $employee->exit_reason ?? '',
                'address' => $employee->address ?? '',
                'emergency_contact_name' => $employee->emergency_contact_name ?? '',
                'emergency_contact_phone' => $employee->emergency_contact_phone ?? '',
                'notes' => $employee->notes ?? '',
            ];
        } else {
            $this->authorize('create', Employee::class);
            $probation = (int) settings('hr.probation_days', 90);
            $this->form = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'job_title' => '',
                'department_id' => '',
                'manager_id' => '',
                'date_of_joining' => now()->toDateString(),
                'employee_code' => '',
                'employment_type' => EmploymentType::FullTime->value,
                'employment_status' => EmploymentStatus::Probation->value,
                'probation_days' => $probation,
                'probation_status' => ProbationStatus::Ongoing->value,
                'probation_end_date' => now()->addDays($probation)->toDateString(),
                'confirmation_date' => '',
                'exit_date' => '',
                'exit_reason' => '',
                'address' => '',
                'emergency_contact_name' => '',
                'emergency_contact_phone' => '',
                'notes' => '',
            ];
        }
    }

    public function save(EmployeeProvisioningService $provisioning): mixed
    {
        $employee = $this->employeeId ? Employee::query()->findOrFail($this->employeeId) : null;
        $employee ? $this->authorize('update', $employee) : $this->authorize('create', Employee::class);

        $userId = $employee?->user_id;

        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.job_title' => ['nullable', 'string', 'max:120'],
            'form.department_id' => ['nullable', 'ulid', 'exists:departments,id'],
            'form.manager_id' => ['nullable', 'ulid', 'exists:users,id', Rule::notIn(array_filter([$userId]))],
            'form.date_of_joining' => ['required', 'date'],
            'form.employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($userId)],
            'form.employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'form.employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'form.probation_days' => ['required', 'integer', 'min:0', 'max:365'],
            'form.probation_status' => ['required', Rule::enum(ProbationStatus::class)],
            'form.probation_end_date' => ['nullable', 'date'],
            'form.confirmation_date' => ['nullable', 'date'],
            'form.exit_date' => ['nullable', 'date'],
            'form.exit_reason' => ['nullable', 'string', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:2000'],
            'form.emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $file = $this->photo instanceof TemporaryUploadedFile ? $this->photo : null;

        if ($employee) {
            $employee = $provisioning->update($employee, $this->form, $file);
        } else {
            $employee = $provisioning->create($this->form, request()->user(), $file);
        }

        session()->flash('status', 'Employee saved.');

        return redirect()->route('employees.show', $employee);
    }

    public function render(): View
    {
        return view('livewire.employees.form', [
            'departments' => Department::query()->orderBy('name')->get(),
            'managers' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'types' => EmploymentType::cases(),
            'statuses' => EmploymentStatus::cases(),
            'probationStatuses' => ProbationStatus::cases(),
            'employee' => $this->employeeId ? Employee::query()->find($this->employeeId) : null,
        ]);
    }
}
