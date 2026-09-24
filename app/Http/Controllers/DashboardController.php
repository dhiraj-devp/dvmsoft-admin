<?php

namespace App\Http\Controllers;

use App\Enums\ChangeRequestStatus;
use App\Enums\FollowUpStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\StageStatus;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('dashboard.view');

        $user = auth()->user();

        $activity = AuditLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $openProjects = Project::query()->open()->count();
        $newLeads = Lead::query()->where('status', LeadStatus::New)->count();
        $openTickets = Ticket::query()->open()->count();
        $outstanding = (float) Invoice::query()->outstanding()->sum('balance');
        $revenue = (float) Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->sum('total');

        $pendingActions = FollowUp::query()->where('status', FollowUpStatus::Pending)->count()
            + LeaveRequest::query()->where('status', LeaveRequestStatus::Pending)->count()
            + ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count()
            + ProjectStage::query()->where('status', StageStatus::ReadyForReview->value)->count();

        return view('dashboard', [
            'metrics' => [
                [
                    'key' => 'revenue',
                    'label' => 'Revenue',
                    'value' => $user->hasPermission('invoices.view') ? money($revenue) : '—',
                    'hint' => $user->hasPermission('invoices.view')
                        ? 'Issued invoices excluding drafts'
                        : 'Finance access required',
                    'icon' => 'currency',
                    'url' => $user->hasPermission('finance.dashboard.view') ? route('finance.dashboard') : null,
                ],
                [
                    'key' => 'outstanding',
                    'label' => 'Outstanding',
                    'value' => $user->hasPermission('invoices.view') ? money($outstanding) : '—',
                    'hint' => $user->hasPermission('invoices.view')
                        ? 'Open receivable balance'
                        : 'Finance access required',
                    'icon' => 'clock',
                    'url' => $user->hasPermission('invoices.view') ? route('invoices.index') : null,
                ],
                [
                    'key' => 'projects',
                    'label' => 'Active Projects',
                    'value' => $user->hasPermission('projects.view') ? (string) $openProjects : '—',
                    'hint' => $user->hasPermission('projects.view')
                        ? 'Planning, active, and on hold'
                        : 'Projects access required',
                    'icon' => 'folder',
                    'url' => $user->hasPermission('projects.view') ? route('projects.dashboard') : null,
                ],
                [
                    'key' => 'leads',
                    'label' => 'New Leads',
                    'value' => $user->hasPermission('leads.view') ? (string) $newLeads : '—',
                    'hint' => $user->hasPermission('leads.view')
                        ? 'Waiting first contact'
                        : 'CRM access required',
                    'icon' => 'briefcase',
                    'url' => $user->hasPermission('sales.view') ? route('sales.dashboard') : null,
                ],
                [
                    'key' => 'tickets',
                    'label' => 'Open Tickets',
                    'value' => $user->hasPermission('tickets.view') ? (string) $openTickets : '—',
                    'hint' => $user->hasPermission('tickets.view')
                        ? 'Open, in progress, or waiting on the client'
                        : 'Support access required',
                    'icon' => 'lifebuoy',
                    'url' => $user->hasPermission('support.dashboard.view') ? route('support.dashboard') : null,
                ],
                [
                    'key' => 'actions',
                    'label' => 'Pending Actions',
                    'value' => (string) $pendingActions,
                    'hint' => 'Follow-ups, leave, change requests, and stage reviews',
                    'icon' => 'check',
                ],
            ],
            'activity' => $activity,
            'userCount' => User::query()->count(),
        ]);
    }
}
