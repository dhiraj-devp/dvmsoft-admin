<?php

namespace App\Services\Ai;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\Reports\FinanceReportService;
use App\Support\ReportPeriod;

class FinanceInsightAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(): array
    {
        return $this->generate('finance', $this->context());
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        $period = ReportPeriod::resolve('this_month');
        $finance = app(FinanceReportService::class)->build($period);

        $overdue = Invoice::query()
            ->with(['client', 'project'])
            ->where('status', InvoiceStatus::Overdue->value)
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'number' => $invoice->number,
                'client' => $invoice->client?->name,
                'project' => $invoice->project?->number,
                'balance' => (float) $invoice->balance,
                'days_overdue' => $invoice->daysOverdue(),
            ]);

        $outstanding = Invoice::query()
            ->outstanding()
            ->with('client')
            ->orderByDesc('balance')
            ->limit(20)
            ->get()
            ->groupBy(fn (Invoice $invoice) => $invoice->client?->name ?: 'Unknown')
            ->map(fn ($invoices, $name) => [
                'client' => $name,
                'invoices' => $invoices->count(),
                'balance' => (float) $invoices->sum('balance'),
            ])
            ->sortByDesc('balance')
            ->values();

        return [
            'period' => $period->preset,
            'kpis' => [
                'outstanding' => $finance['kpis']['outstanding'] ?? 0,
                'overdue' => $finance['kpis']['overdue'] ?? 0,
                'collected' => $finance['kpis']['collected'] ?? 0,
                'expenses' => $finance['kpis']['expenses'] ?? 0,
            ],
            'overdue_invoices' => $overdue->all(),
            'outstanding_by_client' => $outstanding->all(),
            'expense_patterns' => $this->expensePatterns(),
            'rules' => [
                'not_accounting_or_legal_advice' => true,
                'do_not_modify_financial_records' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function expensePatterns(): array
    {
        $months = collect(range(0, 5))->map(fn (int $offset) => now()->copy()->subMonths($offset)->startOfMonth());

        $rows = Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->where('expense_date', '>=', $months->last()->toDateString())
            ->get(['category', 'amount', 'tax_amount', 'expense_date']);

        if ($rows->count() < 6 || $months->count() < 3) {
            return [
                'enough_history' => false,
                'note' => 'Not enough historical expense data to identify unusual patterns.',
            ];
        }

        $byMonthCategory = $rows->groupBy(function (Expense $expense) {
            return $expense->expense_date?->format('Y-m').'|'.$expense->category;
        })->map(fn ($group) => (float) $group->sum(fn (Expense $expense) => $expense->total()));

        $current = now()->format('Y-m');
        $unusual = [];

        foreach ($rows->pluck('category')->unique() as $category) {
            $history = $months->skip(1)->map(fn ($month) => (float) ($byMonthCategory[$month->format('Y-m').'|'.$category] ?? 0));
            $average = $history->avg();
            $currentAmount = (float) ($byMonthCategory[$current.'|'.$category] ?? 0);

            if ($average >= 1000 && $currentAmount > ($average * 2)) {
                $unusual[] = [
                    'category' => $category,
                    'current' => $currentAmount,
                    'recent_average' => round((float) $average, 2),
                ];
            }
        }

        return [
            'enough_history' => true,
            'unusual' => $unusual,
        ];
    }
}
