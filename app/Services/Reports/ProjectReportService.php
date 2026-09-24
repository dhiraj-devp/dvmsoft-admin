<?php

namespace App\Services\Reports;

use App\Enums\MilestoneStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectProfitabilityService;
use App\Support\ReportPeriod;

class ProjectReportService
{
    public function __construct(protected ProjectProfitabilityService $profitability) {}

    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period): array
    {
        $created = Project::query();
        $period->apply($created, 'created_at');

        $byStatus = collect(ProjectStatus::cases())->map(fn (ProjectStatus $status) => [
            'label' => $status->label(),
            'value' => Project::query()->where('status', $status->value)->count(),
        ])->all();

        $byHealth = collect(ProjectHealth::cases())->map(fn (ProjectHealth $health) => [
            'label' => $health->label(),
            'value' => Project::query()->where('health', $health->value)->count(),
        ])->all();

        $total = Project::query()->count();
        $completed = Project::query()->where('status', ProjectStatus::Completed->value)->count();
        $completedInPeriod = Project::query()->where('status', ProjectStatus::Completed->value);
        $period->applyDate($completedInPeriod, 'actual_completion_date');
        $completedPeriodCount = $completedInPeriod->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        $delayed = Project::query()
            ->with('client')
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', now()->toDateString())
            ->whereNotIn('status', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value])
            ->orderBy('expected_end_date')
            ->get();

        $tasksTotal = Task::query()->count();
        $tasksCompleted = Task::query()->where('status', TaskStatus::Completed->value)->count();
        $tasksCompletedInPeriod = Task::query()->where('status', TaskStatus::Completed->value);
        $period->apply($tasksCompletedInPeriod, 'completed_at');
        $taskRate = $tasksTotal > 0 ? round(($tasksCompleted / $tasksTotal) * 100, 1) : 0.0;

        $milestonesTotal = Milestone::query()->count();
        $milestonesCompleted = Milestone::query()->where('status', MilestoneStatus::Completed->value)->count();
        $milestonesCompletedInPeriod = Milestone::query()->where('status', MilestoneStatus::Completed->value);
        $period->apply($milestonesCompletedInPeriod, 'completed_at');
        $milestoneRate = $milestonesTotal > 0 ? round(($milestonesCompleted / $milestonesTotal) * 100, 1) : 0.0;

        $profitRows = $this->profitability->rows();

        return [
            'metrics' => [
                ['label' => 'Projects', 'value' => $total, 'hint' => (clone $created)->count().' created in period', 'icon' => 'folder'],
                ['label' => 'Project completion', 'value' => $completionRate.'%', 'hint' => $completedPeriodCount.' completed in period', 'icon' => 'check'],
                ['label' => 'Delayed projects', 'value' => $delayed->count(), 'hint' => 'Past expected end date', 'icon' => 'alert'],
                ['label' => 'Task completion', 'value' => $taskRate.'%', 'hint' => $tasksCompletedInPeriod->count().' done in period', 'icon' => 'check'],
                ['label' => 'Milestone completion', 'value' => $milestoneRate.'%', 'hint' => $milestonesCompletedInPeriod->count().' done in period', 'icon' => 'clock'],
                ['label' => 'Active projects', 'value' => Project::query()->where('status', ProjectStatus::Active->value)->count(), 'hint' => 'Currently in delivery', 'icon' => 'folder'],
            ],
            'charts' => [
                ['title' => 'Projects by status', 'items' => $this->withDisplay($byStatus)],
                ['title' => 'Projects by health', 'items' => $this->withDisplay($byHealth)],
            ],
            'tables' => [
                [
                    'title' => 'Delayed projects',
                    'headers' => ['Project', 'Client', 'Expected end', 'Status', 'Health'],
                    'rows' => $delayed->map(fn (Project $project) => [
                        $project->number.' · '.$project->name,
                        $project->client?->name,
                        $project->expected_end_date?->format(settings('company.date_format', 'd M Y')),
                        $project->status->label(),
                        $project->health->label(),
                    ])->all(),
                    'export' => $delayed->map(fn (Project $project) => [
                        $project->number,
                        $project->name,
                        $project->client?->name,
                        $project->expected_end_date?->toDateString(),
                        $project->status->value,
                        $project->health->value,
                    ])->all(),
                ],
                [
                    'title' => 'Budget vs actual / profitability',
                    'headers' => ['Project', 'Budget', 'Invoiced', 'Paid', 'Expenses', 'Estimated profit', 'Actual profit'],
                    'rows' => collect($profitRows)->map(fn (array $row) => [
                        $row['project']->number.' · '.$row['project']->name,
                        money($row['budget']),
                        money($row['invoiced']),
                        money($row['paid']),
                        money($row['expenses']),
                        money($row['estimated_profit']),
                        money($row['actual_profit']),
                    ])->all(),
                    'export' => collect($profitRows)->map(fn (array $row) => [
                        $row['project']->number,
                        $row['project']->name,
                        $row['budget'],
                        $row['invoiced'],
                        $row['paid'],
                        $row['expenses'],
                        $row['estimated_profit'],
                        $row['actual_profit'],
                    ])->all(),
                ],
            ],
            'kpis' => [
                'active_projects' => Project::query()->where('status', ProjectStatus::Active->value)->count(),
                'total_projects' => $total,
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
