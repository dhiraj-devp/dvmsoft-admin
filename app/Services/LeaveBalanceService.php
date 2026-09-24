<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;

class LeaveBalanceService
{
    public function ensureYear(Employee $employee, int $year): void
    {
        LeaveType::query()->where('is_active', true)->each(function (LeaveType $type) use ($employee, $year): void {
            LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'entitled' => $type->days_per_year,
                    'used' => 0,
                    'pending' => 0,
                ]
            );
        });
    }

    public function for(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        $this->ensureYear($employee, $year);

        return LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ],
            [
                'entitled' => $type->days_per_year,
                'used' => 0,
                'pending' => 0,
            ]
        );
    }
}
