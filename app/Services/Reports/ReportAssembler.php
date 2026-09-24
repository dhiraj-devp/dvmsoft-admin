<?php

namespace App\Services\Reports;

use App\Models\User;
use App\Support\ReportPeriod;
use InvalidArgumentException;

class ReportAssembler
{
    public function __construct(
        protected OverviewReportService $overview,
        protected SalesReportService $sales,
        protected ProjectReportService $projects,
        protected FinanceReportService $finance,
        protected HrReportService $hr,
        protected SupportReportService $support,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(string $section, ReportPeriod $period, User $user): array
    {
        return match ($section) {
            'overview' => $this->overview->build($period, $user),
            'sales' => $this->sales->build($period),
            'projects' => $this->projects->build($period),
            'finance' => $this->finance->build($period),
            'hr' => $this->hr->build($period),
            'support' => $this->support->build($period),
            default => throw new InvalidArgumentException('Unknown report section.'),
        };
    }

    public function permission(string $section): string
    {
        return (string) config('reports.sections.'.$section.'.permission', 'reports.view');
    }
}
