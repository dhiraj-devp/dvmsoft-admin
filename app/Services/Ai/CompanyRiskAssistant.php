<?php

namespace App\Services\Ai;

use App\Enums\ChangeRequestStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\ChangeRequest;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\SupportReportService;
use App\Support\ReportPeriod;

class CompanyRiskAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(User $user): array
    {
        return $this->generate('overview', $this->context($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function context(User $user): array
    {
        $period = ReportPeriod::resolve('this_month');
        $snapshot = [];

        if ($user->hasPermission('reports.sales.view') || $user->hasPermission('leads.view')) {
            $sales = app(SalesReportService::class)->build($period);
            $snapshot['sales'] = [
                'leads_created' => $sales['kpis']['leads'] ?? 0,
                'pipeline_value' => $sales['kpis']['pipeline_value'] ?? 0,
                'open_leads' => Lead::query()->open()->count(),
                'stale_open_leads' => Lead::query()->open()->where('updated_at', '<', now()->subDays(14))->count(),
                'overdue_follow_ups' => FollowUp::query()->pending()->where('scheduled_at', '<', now())->count(),
                'new_leads' => Lead::query()->where('status', LeadStatus::New->value)->count(),
            ];
        }

        if ($user->hasPermission('reports.projects.view') || $user->hasPermission('projects.view')) {
            $projects = app(ProjectReportService::class)->build($period);
            $snapshot['projects'] = [
                'active' => $projects['kpis']['active_projects'] ?? 0,
                'red_health' => Project::query()->where('health', ProjectHealth::Red->value)->count(),
                'delayed' => Project::query()
                    ->whereNotNull('expected_end_date')
                    ->where('expected_end_date', '<', now()->toDateString())
                    ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value])
                    ->count(),
                'overdue_tasks' => Task::query()->open()->whereNotNull('due_date')->where('due_date', '<', now()->toDateString())->count(),
                'blocked_tasks' => Task::query()->where('status', TaskStatus::Blocked->value)->count(),
                'pending_change_requests' => ChangeRequest::query()->where('status', ChangeRequestStatus::Pending->value)->count(),
            ];
        }

        if ($user->hasPermission('reports.finance.view') || $user->hasPermission('finance.dashboard.view') || $user->hasPermission('invoices.view')) {
            $finance = app(FinanceReportService::class)->build($period);
            $snapshot['finance'] = [
                'outstanding' => $finance['kpis']['outstanding'] ?? 0,
                'overdue' => $finance['kpis']['overdue'] ?? 0,
                'overdue_invoice_count' => Invoice::query()->where('status', InvoiceStatus::Overdue->value)->count(),
            ];
        }

        if ($user->hasPermission('reports.support.view') || $user->hasPermission('tickets.view')) {
            $support = app(SupportReportService::class)->build($period);
            $snapshot['support'] = [
                'open_tickets' => $support['kpis']['open_tickets'] ?? 0,
                'sla_breaches' => Ticket::query()->breached()->count(),
                'unassigned_open' => Ticket::query()->open()->whereNull('assigned_to_id')->count(),
            ];
        }

        return [
            'period' => $period->preset,
            'snapshot' => $snapshot,
        ];
    }
}
