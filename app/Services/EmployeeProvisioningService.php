<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\HrChecklistType;
use App\Enums\ProbationStatus;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeProvisioningService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected LeaveBalanceService $balances,
        protected HrChecklistService $checklists,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $actor, ?UploadedFile $photo = null): Employee
    {
        return DB::transaction(function () use ($attributes, $photo) {
            $user = User::query()->where('email', $attributes['email'])->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => $attributes['name'],
                    'email' => $attributes['email'],
                    'password' => $attributes['password'] ?? Str::password(16),
                    'phone' => $attributes['phone'] ?: null,
                    'employee_code' => $attributes['employee_code'] ?: $this->numbers->nextEmployee(),
                    'job_title' => $attributes['job_title'] ?: null,
                    'department_id' => $attributes['department_id'] ?: null,
                    'manager_id' => $attributes['manager_id'] ?: null,
                    'date_of_joining' => $attributes['date_of_joining'] ?: now()->toDateString(),
                    'is_active' => ($attributes['employment_status'] ?? EmploymentStatus::Probation->value) !== EmploymentStatus::Exited->value,
                    'email_verified_at' => now(),
                ]);

                $employeeRole = Role::query()->where('slug', 'employee')->first();
                if ($employeeRole) {
                    $user->roles()->syncWithoutDetaching([$employeeRole->id]);
                }
            } else {
                $user->fill([
                    'name' => $attributes['name'],
                    'phone' => $attributes['phone'] ?: $user->phone,
                    'job_title' => $attributes['job_title'] ?: $user->job_title,
                    'department_id' => $attributes['department_id'] ?: $user->department_id,
                    'manager_id' => $attributes['manager_id'] ?: $user->manager_id,
                    'date_of_joining' => $attributes['date_of_joining'] ?: $user->date_of_joining,
                ]);

                if (blank($user->employee_code)) {
                    $user->employee_code = $this->numbers->nextEmployee();
                }

                $user->save();
            }

            $employee = $user->employee()->withTrashed()->first();

            $payload = $this->profilePayload($attributes, $user);

            if ($employee) {
                $employee->restore();
                $employee->update($payload);
            } else {
                $employee = Employee::query()->create($payload + ['user_id' => $user->id]);
            }

            if ($photo) {
                $this->storePhoto($employee, $photo);
            }

            $this->balances->ensureYear($employee, (int) now()->year);
            $this->checklists->ensure($employee, HrChecklistType::Onboarding);
            $this->checklists->completeItem($employee, HrChecklistType::Onboarding, 'account_created', $user);

            return $employee->fresh(['user.department', 'user.manager']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Employee $employee, array $attributes, ?UploadedFile $photo = null): Employee
    {
        return DB::transaction(function () use ($employee, $attributes, $photo) {
            $user = $employee->user;

            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?: null,
                'job_title' => $attributes['job_title'] ?: null,
                'department_id' => $attributes['department_id'] ?: null,
                'manager_id' => $attributes['manager_id'] ?: null,
                'date_of_joining' => $attributes['date_of_joining'] ?: null,
                'is_active' => ($attributes['employment_status'] ?? $employee->employment_status->value) !== EmploymentStatus::Exited->value,
            ]);

            $employee->update($this->profilePayload($attributes, $user->fresh()));

            if ($photo) {
                $this->storePhoto($employee, $photo);
            }

            $employee = $employee->fresh(['user']);

            if ($employee->employment_status === EmploymentStatus::Exited || $employee->exit_date) {
                $this->checklists->ensure($employee, HrChecklistType::Offboarding);
                $this->checklists->completeItem($employee, HrChecklistType::Offboarding, 'exit_recorded', $user);
            }

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function profilePayload(array $attributes, User $user): array
    {
        $joining = $attributes['date_of_joining'] ? \Carbon\Carbon::parse($attributes['date_of_joining']) : ($user->date_of_joining ?? now());
        $probationDays = (int) ($attributes['probation_days'] ?? settings('hr.probation_days', 90));
        $status = EmploymentStatus::from($attributes['employment_status']);
        $probation = ProbationStatus::from($attributes['probation_status']);

        if ($status === EmploymentStatus::Exited) {
            $probation = $probation === ProbationStatus::Ongoing ? ProbationStatus::NotApplicable : $probation;
        }

        return [
            'employment_type' => EmploymentType::from($attributes['employment_type']),
            'employment_status' => $status,
            'probation_days' => $probationDays,
            'probation_status' => $probation,
            'probation_end_date' => $attributes['probation_end_date'] ?: $joining->copy()->addDays($probationDays)->toDateString(),
            'confirmation_date' => $attributes['confirmation_date'] ?: null,
            'exit_date' => $attributes['exit_date'] ?: null,
            'exit_reason' => $attributes['exit_reason'] ?: null,
            'address' => $attributes['address'] ?: null,
            'emergency_contact_name' => $attributes['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $attributes['emergency_contact_phone'] ?: null,
            'notes' => $attributes['notes'] ?: null,
            'photo_disk' => 'hr',
        ];
    }

    protected function storePhoto(Employee $employee, UploadedFile $photo): void
    {
        if ($employee->photo_path) {
            \Illuminate\Support\Facades\Storage::disk($employee->photo_disk ?: 'hr')->delete($employee->photo_path);
        }

        $path = $photo->store('employees/'.$employee->id, 'hr');

        $employee->forceFill([
            'photo_path' => $path,
            'photo_disk' => 'hr',
        ])->save();
    }
}
