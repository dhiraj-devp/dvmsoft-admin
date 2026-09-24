<?php

namespace App\Services;

use App\Enums\LeaveRequestStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;

class HrMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $employees = Employee::query()->with(['user', 'documents']);

        $missingDocuments = Employee::query()
            ->with('documents')
            ->get()
            ->filter(fn (Employee $employee) => $employee->missingDocumentTypes() !== [])
            ->count();

        return [
            'metrics' => [
                ['label' => 'Total employees', 'value' => (clone $employees)->count(), 'hint' => 'All HR records', 'icon' => 'users'],
                ['label' => 'Active employees', 'value' => Employee::query()->active()->count(), 'hint' => 'Not exited', 'icon' => 'check'],
                ['label' => 'On probation', 'value' => Employee::query()->onProbation()->count(), 'hint' => 'Awaiting confirmation', 'icon' => 'clock'],
                ['label' => 'Pending leave', 'value' => LeaveRequest::query()->where('status', LeaveRequestStatus::Pending->value)->count(), 'hint' => 'Needs a decision', 'icon' => 'clock'],
                ['label' => 'Upcoming joiners', 'value' => Employee::query()->whereHas('user', fn ($users) => $users->whereDate('date_of_joining', '>', now()->toDateString())->whereDate('date_of_joining', '<=', now()->addDays(30)->toDateString()))->count(), 'hint' => 'Next 30 days', 'icon' => 'plus'],
                ['label' => 'Upcoming exits', 'value' => Employee::query()->whereNotNull('exit_date')->whereDate('exit_date', '>=', now()->toDateString())->whereDate('exit_date', '<=', now()->addDays(30)->toDateString())->count(), 'hint' => 'Next 30 days', 'icon' => 'logout'],
                ['label' => 'Missing documents', 'value' => $missingDocuments, 'hint' => 'Required files not on file', 'icon' => 'document'],
                ['label' => 'Recent HR activity', 'value' => AuditLog::query()->whereIn('module', ['employees', 'employee_documents', 'leave', 'assets', 'hr_checklists'])->count(), 'hint' => 'Logged HR actions', 'icon' => 'shield'],
            ],
            'pendingLeave' => LeaveRequest::query()
                ->with(['employee.user', 'leaveType'])
                ->where('status', LeaveRequestStatus::Pending->value)
                ->latest()
                ->limit(6)
                ->get(),
            'upcomingJoiners' => Employee::query()
                ->with('user')
                ->whereHas('user', fn ($users) => $users->whereDate('date_of_joining', '>', now()->toDateString()))
                ->latest()
                ->limit(6)
                ->get(),
            'recentActivity' => AuditLog::query()
                ->with('user')
                ->whereIn('module', ['employees', 'employee_documents', 'leave', 'assets', 'hr_checklists'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }
}
