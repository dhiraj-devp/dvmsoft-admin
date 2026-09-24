<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;

class ProjectProfitabilityService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return Project::query()
            ->with('client')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => $this->forProject($project))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function forProject(Project $project): array
    {
        $invoiced = (float) Invoice::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->sum('total');

        $paid = (float) Invoice::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->sum('amount_paid');

        $expenses = (float) Expense::query()
            ->where('project_id', $project->id)
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->get()
            ->sum(fn (Expense $expense) => $expense->total());

        $budget = (float) ($project->budget ?? 0);

        return [
            'project' => $project,
            'budget' => $budget,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'expenses' => (float) $expenses,
            'estimated_profit' => round($budget - (float) $expenses, 2),
            'actual_profit' => round($paid - (float) $expenses, 2),
        ];
    }
}
