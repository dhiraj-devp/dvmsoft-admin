<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Support\ReportPeriod;

class OverviewReportService
{
    public function __construct(
        protected SalesReportService $sales,
        protected ProjectReportService $projects,
        protected FinanceReportService $finance,
        protected HrReportService $hr,
        protected SupportReportService $support,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period, User $user): array
    {
        $metrics = [];

        if ($user->hasPermission('reports.finance.view')) {
            $finance = $this->finance->build($period);
            $metrics = array_merge($metrics, [
                ['label' => 'Revenue', 'value' => $finance['metrics'][0]['value'], 'hint' => 'Billed in period', 'icon' => 'currency', 'section' => 'finance'],
                ['label' => 'Expenses', 'value' => $finance['metrics'][1]['value'], 'hint' => 'Costs in period', 'icon' => 'folder', 'section' => 'finance'],
                ['label' => 'Profit', 'value' => $finance['metrics'][2]['value'], 'hint' => 'Collected minus expenses', 'icon' => 'chart', 'section' => 'finance'],
                ['label' => 'Outstanding', 'value' => $finance['metrics'][3]['value'], 'hint' => 'Current receivables', 'icon' => 'clock', 'section' => 'finance'],
            ]);
        }

        if ($user->hasPermission('reports.projects.view')) {
            $projects = $this->projects->build($period);
            $metrics[] = ['label' => 'Active projects', 'value' => $projects['kpis']['active_projects'], 'hint' => 'In delivery now', 'icon' => 'folder', 'section' => 'projects'];
        }

        if ($user->hasPermission('reports.support.view')) {
            $support = $this->support->build($period);
            $metrics[] = ['label' => 'Open tickets', 'value' => $support['kpis']['open_tickets'], 'hint' => 'Current support queue', 'icon' => 'lifebuoy', 'section' => 'support'];
        }

        if ($user->hasPermission('reports.hr.view')) {
            $hr = $this->hr->build($period);
            $metrics[] = ['label' => 'Employees', 'value' => $hr['kpis']['employees'], 'hint' => 'HR records on file', 'icon' => 'users', 'section' => 'hr'];
        }

        if ($user->hasPermission('reports.sales.view')) {
            $sales = $this->sales->build($period);
            $metrics[] = ['label' => 'Leads', 'value' => $sales['kpis']['leads'], 'hint' => 'Created in period', 'icon' => 'briefcase', 'section' => 'sales'];
            $metrics[] = ['label' => 'Pipeline value', 'value' => money($sales['kpis']['pipeline_value']), 'hint' => 'Open opportunities', 'icon' => 'currency', 'section' => 'sales'];
        }

        return [
            'metrics' => $metrics,
            'charts' => [],
            'tables' => [
                [
                    'title' => 'Company snapshot',
                    'headers' => ['Metric', 'Value'],
                    'rows' => collect($metrics)->map(fn (array $metric) => [$metric['label'], $metric['value']])->all(),
                    'export' => collect($metrics)->map(fn (array $metric) => [$metric['label'], $metric['value']])->all(),
                ],
            ],
        ];
    }
}
