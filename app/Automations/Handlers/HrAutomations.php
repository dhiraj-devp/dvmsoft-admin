<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Automations\StaffNotifier;
use App\Enums\EmployeeDocumentStatus;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Automation;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;

class HrAutomations implements AutomationHandler
{
    public function __construct(protected StaffNotifier $staff) {}

    public function handle(Automation $automation): AutomationResult
    {
        return match ($automation->key) {
            'hr.leave_pending' => $this->leavePending($automation),
            'hr.employee_joining' => $this->joining($automation),
            'hr.employee_exit' => $this->exit($automation),
            'hr.document_alerts' => $this->documents($automation),
            default => new AutomationResult,
        };
    }

    protected function leavePending(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;
        $approvers = $this->staff->withPermission('leave.approve');

        $requests = LeaveRequest::query()
            ->with(['employee.user', 'leaveType'])
            ->where('status', LeaveRequestStatus::Pending->value)
            ->get();

        foreach ($requests as $request) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $approvers,
                'Leave pending approval',
                ($request->employee?->name() ?? 'An employee').' has a pending '.$request->leaveType?->name.' request.',
                url(route('leave.index', [], false)),
                ['leave_request_id' => $request->id],
                $request,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function joining(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.hr_joining_days', 7));
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');
        $hr = $this->staff->withPermission('hr.dashboard.view');

        $employees = Employee::query()
            ->with('user')
            ->where('employment_status', '!=', EmploymentStatus::Exited->value)
            ->whereHas('user', function ($query) use ($days) {
                $query->whereDate('date_of_joining', '>', now()->toDateString())
                    ->whereDate('date_of_joining', '<=', now()->addDays($days)->toDateString());
            })
            ->get();

        foreach ($employees as $employee) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $hr,
                'Upcoming employee joining',
                $employee->name().' joins on '.$employee->user?->date_of_joining?->format($format).'.',
                url(route('employees.show', $employee, false)),
                ['employee_id' => $employee->id],
                $employee,
                $employee->user?->date_of_joining?->toDateString() ?? now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function exit(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.hr_exit_days', 7));
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');
        $hr = $this->staff->withPermission('hr.dashboard.view');

        $employees = Employee::query()
            ->with('user')
            ->whereNotNull('exit_date')
            ->where('employment_status', '!=', EmploymentStatus::Exited->value)
            ->whereDate('exit_date', '>=', now()->toDateString())
            ->whereDate('exit_date', '<=', now()->addDays($days)->toDateString())
            ->get();

        foreach ($employees as $employee) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $hr,
                'Upcoming employee exit',
                $employee->name().' exit date is '.$employee->exit_date->format($format).'.',
                url(route('employees.show', $employee, false)),
                ['employee_id' => $employee->id],
                $employee,
                $employee->exit_date->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function documents(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.hr_document_expiry_days', 30));
        $result = new AutomationResult;
        $hr = $this->staff->withPermission('employee_documents.view');
        $format = settings('company.date_format', 'd M Y');

        $employees = Employee::query()
            ->with(['user', 'documents'])
            ->where('employment_status', '!=', EmploymentStatus::Exited->value)
            ->get();

        foreach ($employees as $employee) {
            $missing = $employee->missingDocumentTypes();

            if ($missing === []) {
                continue;
            }

            $result->processed++;
            $labels = collect($missing)
                ->map(fn (string $type) => config('hr.document_types.'.$type, $type))
                ->implode(', ');

            $result->notified += $this->staff->send(
                $automation->key,
                $hr,
                'Missing HR documents',
                $employee->name().' is missing: '.$labels.'.',
                url(route('employees.show', $employee, false)),
                ['employee_id' => $employee->id],
                $employee,
                'missing-'.now()->toDateString(),
                $automation->channels,
            );
        }

        $documents = EmployeeDocument::query()
            ->with(['employee.user'])
            ->whereNotNull('expiry_date')
            ->where('status', '!=', EmployeeDocumentStatus::Rejected->value)
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->get();

        foreach ($documents as $document) {
            if ($document->employee?->employment_status === EmploymentStatus::Exited) {
                continue;
            }

            $result->processed++;
            $expired = $document->isExpired();

            $result->notified += $this->staff->send(
                $automation->key,
                $hr,
                $expired ? 'HR document expired' : 'HR document expiring',
                ($document->employee?->name() ?? 'An employee').' · '.$document->title.' '
                    .($expired ? 'expired' : 'expires').' on '.$document->expiry_date->format($format).'.',
                $document->employee ? url(route('employees.show', $document->employee, false)) : null,
                ['employee_document_id' => $document->id],
                $document,
                ($expired ? 'expired-' : 'expiring-').$document->expiry_date->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }
}
