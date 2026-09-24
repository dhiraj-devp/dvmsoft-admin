<?php

namespace App\Services\Reports;

use App\Enums\EmploymentType;
use App\Enums\LeaveRequestStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Support\ReportPeriod;

class HrReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period): array
    {
        $total = Employee::query()->count();
        $active = Employee::query()->active()->count();

        $byDepartment = Department::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => [
                'label' => $department->name,
                'value' => Employee::query()->whereHas('user', fn ($users) => $users->where('department_id', $department->id))->count(),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values()
            ->all();

        $unassigned = Employee::query()->whereHas('user', fn ($users) => $users->whereNull('department_id'))->count();
        if ($unassigned > 0) {
            $byDepartment[] = ['label' => 'Unassigned', 'value' => $unassigned];
        }

        $byType = collect(EmploymentType::cases())->map(fn (EmploymentType $type) => [
            'label' => $type->label(),
            'value' => Employee::query()->where('employment_type', $type->value)->count(),
        ])->all();

        $leaveQuery = LeaveRequest::query();
        $leaveQuery->where(function ($query) use ($period) {
            $query->whereBetween('start_date', [$period->from->toDateString(), $period->to->toDateString()])
                ->orWhereBetween('end_date', [$period->from->toDateString(), $period->to->toDateString()])
                ->orWhere(function ($inner) use ($period) {
                    $inner->where('start_date', '<=', $period->from->toDateString())
                        ->where('end_date', '>=', $period->to->toDateString());
                });
        });

        $leaveByStatus = collect(LeaveRequestStatus::cases())->map(fn (LeaveRequestStatus $status) => [
            'label' => $status->label(),
            'value' => (clone $leaveQuery)->where('status', $status->value)->count(),
            'days' => (float) (clone $leaveQuery)->where('status', $status->value)->sum('days'),
        ])->all();

        $joiners = Employee::query()
            ->with(['user.department'])
            ->whereHas('user', fn ($users) => $users->whereBetween('date_of_joining', [$period->from->toDateString(), $period->to->toDateString()]))
            ->get();

        $exits = Employee::query()
            ->with('user')
            ->whereNotNull('exit_date')
            ->whereBetween('exit_date', [$period->from->toDateString(), $period->to->toDateString()])
            ->get();

        $upcomingJoiners = Employee::query()
            ->with('user')
            ->whereHas('user', fn ($users) => $users->whereDate('date_of_joining', '>', now()->toDateString())->whereDate('date_of_joining', '<=', now()->addDays(30)->toDateString()))
            ->get();

        $upcomingExits = Employee::query()
            ->with('user')
            ->whereNotNull('exit_date')
            ->whereDate('exit_date', '>=', now()->toDateString())
            ->whereDate('exit_date', '<=', now()->addDays(30)->toDateString())
            ->get();

        return [
            'metrics' => [
                ['label' => 'Total employees', 'value' => $total, 'hint' => 'HR records on file', 'icon' => 'users'],
                ['label' => 'Active employees', 'value' => $active, 'hint' => 'Not exited', 'icon' => 'check'],
                ['label' => 'Joiners in period', 'value' => $joiners->count(), 'hint' => 'Started in selected range', 'icon' => 'plus'],
                ['label' => 'Exits in period', 'value' => $exits->count(), 'hint' => 'Exit date in range', 'icon' => 'logout'],
                ['label' => 'Upcoming joiners', 'value' => $upcomingJoiners->count(), 'hint' => 'Next 30 days', 'icon' => 'clock'],
                ['label' => 'Upcoming exits', 'value' => $upcomingExits->count(), 'hint' => 'Next 30 days', 'icon' => 'clock'],
            ],
            'charts' => [
                ['title' => 'Employees by department', 'items' => $this->withDisplay($byDepartment)],
                ['title' => 'Employees by employment type', 'items' => $this->withDisplay($byType)],
                ['title' => 'Leave in period', 'items' => collect($leaveByStatus)->map(fn (array $row) => [
                    'label' => $row['label'],
                    'value' => $row['value'],
                    'display' => $row['value'].' · '.$row['days'].' days',
                ])->all()],
            ],
            'tables' => [
                [
                    'title' => 'Leave statistics',
                    'headers' => ['Status', 'Requests', 'Days'],
                    'rows' => collect($leaveByStatus)->map(fn (array $row) => [$row['label'], $row['value'], $row['days']])->all(),
                    'export' => collect($leaveByStatus)->map(fn (array $row) => [$row['label'], $row['value'], $row['days']])->all(),
                ],
                [
                    'title' => 'Joiners in period',
                    'headers' => ['Employee', 'Joining date', 'Department'],
                    'rows' => $joiners->map(fn (Employee $employee) => [
                        $employee->name(),
                        $employee->user?->date_of_joining?->format(settings('company.date_format', 'd M Y')),
                        $employee->user?->department?->name ?: '—',
                    ])->all(),
                    'export' => $joiners->map(fn (Employee $employee) => [
                        $employee->name(),
                        $employee->user?->date_of_joining?->toDateString(),
                        $employee->user?->department?->name,
                    ])->all(),
                ],
                [
                    'title' => 'Exits in period',
                    'headers' => ['Employee', 'Exit date'],
                    'rows' => $exits->map(fn (Employee $employee) => [
                        $employee->name(),
                        $employee->exit_date?->format(settings('company.date_format', 'd M Y')),
                    ])->all(),
                    'export' => $exits->map(fn (Employee $employee) => [
                        $employee->name(),
                        $employee->exit_date?->toDateString(),
                    ])->all(),
                ],
            ],
            'kpis' => [
                'employees' => $total,
                'active_employees' => $active,
            ],
        ];
    }

    /**
     * @param  list<array{label: string, value: int|float}>  $items
     * @return list<array{label: string, value: int|float, display: string}>
     */
    protected function withDisplay(array $items): array
    {
        return collect($items)->map(fn (array $item) => $item + ['display' => (string) $item['value']])->all();
    }
}
