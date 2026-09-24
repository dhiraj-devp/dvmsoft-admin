<?php

namespace App\Http\Controllers;

use App\Enums\HrChecklistType;
use App\Models\Employee;
use App\Services\HrChecklistService;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        return view('employees.index');
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create');
    }

    public function show(Employee $employee, HrChecklistService $checklists): View
    {
        $this->authorize('view', $employee);

        $tab = request()->string('tab', 'overview')->toString();
        $allowed = ['overview', 'documents', 'leave', 'assets', 'onboarding', 'offboarding'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'overview';
        }

        $employee->load(['user.department', 'user.manager', 'documents', 'leaveBalances.leaveType', 'assets']);
        $checklists->ensure($employee, HrChecklistType::Onboarding);
        if ($employee->exit_date || $employee->employment_status->value === 'exited') {
            $checklists->ensure($employee, HrChecklistType::Offboarding);
        }

        return view('employees.show', [
            'employee' => $employee->fresh(['user.department', 'user.manager', 'onboarding.items', 'offboarding.items']),
            'tab' => $tab,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        return view('employees.edit', compact('employee'));
    }
}
