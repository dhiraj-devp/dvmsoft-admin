<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;

class FinanceMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $billed = Invoice::query()->whereNotIn('status', [
            InvoiceStatus::Draft->value,
            InvoiceStatus::Cancelled->value,
        ]);

        $totalRevenue = (float) (clone $billed)->sum('total');
        $paidRevenue = (float) (clone $billed)->sum('amount_paid');
        $outstanding = (float) Invoice::query()->outstanding()->sum('balance');
        $overdue = (float) Invoice::query()
            ->where('status', InvoiceStatus::Overdue->value)
            ->sum('balance');

        $expensesQuery = Expense::query()->where('status', '!=', ExpenseStatus::Rejected->value);
        $expenses = (float) (clone $expensesQuery)->get()->sum(fn (Expense $expense) => $expense->total());

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthRevenue = (float) Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Cancelled->value])
            ->whereBetween('invoice_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('total');

        $monthExpenses = (float) Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->sum(fn (Expense $expense) => $expense->total());

        return [
            'metrics' => [
                ['label' => 'Total revenue', 'value' => money($totalRevenue), 'hint' => 'Issued invoices excluding drafts', 'icon' => 'currency'],
                ['label' => 'Paid revenue', 'value' => money($paidRevenue), 'hint' => 'Collected against invoices', 'icon' => 'check'],
                ['label' => 'Outstanding', 'value' => money($outstanding), 'hint' => 'Open receivable balance', 'icon' => 'clock'],
                ['label' => 'Overdue', 'value' => money($overdue), 'hint' => 'Past due unpaid balance', 'icon' => 'clock'],
                ['label' => 'Expenses', 'value' => money($expenses), 'hint' => 'Approved and paid costs', 'icon' => 'folder'],
                ['label' => 'Net profit', 'value' => money($paidRevenue - $expenses), 'hint' => 'Paid revenue minus expenses', 'icon' => 'chart'],
                ['label' => 'This month revenue', 'value' => money($monthRevenue), 'hint' => now()->format('F Y'), 'icon' => 'currency'],
                ['label' => 'This month expenses', 'value' => money($monthExpenses), 'hint' => now()->format('F Y'), 'icon' => 'folder'],
            ],
            'recentInvoices' => Invoice::query()->with('client')->latest()->limit(6)->get(),
            'recentPayments' => Payment::query()->with(['invoice', 'client'])->latest('paid_on')->limit(6)->get(),
            'overdueInvoices' => Invoice::query()
                ->with(['client', 'project'])
                ->where('status', InvoiceStatus::Overdue->value)
                ->orderBy('due_date')
                ->limit(8)
                ->get(),
        ];
    }
}
