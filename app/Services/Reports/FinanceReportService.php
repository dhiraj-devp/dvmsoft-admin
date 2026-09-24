<?php

namespace App\Services\Reports;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;

class FinanceReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period): array
    {
        $revenue = $this->periodRevenue($period);
        $collected = $this->periodCollected($period);
        $expenses = $this->periodExpenses($period);
        $outstanding = (float) Invoice::query()->outstanding()->sum('balance');
        $overdue = (float) Invoice::query()->where('status', InvoiceStatus::Overdue->value)->sum('balance');
        $profit = round($collected - $expenses, 2);

        $revenueTrend = collect($period->months())->map(function (array $month) {
            $slice = ReportPeriod::resolve('custom', $month['start']->toDateString(), $month['end']->toDateString());

            return [
                'label' => $month['label'],
                'value' => $this->periodRevenue($slice),
                'display' => money($this->periodRevenue($slice)),
            ];
        })->all();

        $expenseTrend = collect($period->months())->map(function (array $month) {
            $slice = ReportPeriod::resolve('custom', $month['start']->toDateString(), $month['end']->toDateString());
            $amount = $this->periodExpenses($slice);

            return [
                'label' => $month['label'],
                'value' => $amount,
                'display' => money($amount),
            ];
        })->all();

        $byClient = $this->billedQuery($period)
            ->with('client')
            ->get()
            ->groupBy(fn (Invoice $invoice) => $invoice->client?->name ?: 'Unknown')
            ->map(fn ($invoices, $name) => [
                'label' => $name,
                'revenue' => (float) $invoices->sum('total'),
                'paid' => (float) $invoices->sum('amount_paid'),
            ])
            ->sortByDesc('revenue')
            ->values();

        $byProject = $this->billedQuery($period)
            ->with('project')
            ->whereNotNull('project_id')
            ->get()
            ->groupBy(fn (Invoice $invoice) => $invoice->project?->number ?: 'Project')
            ->map(fn ($invoices, $label) => [
                'label' => $label.' · '.($invoices->first()->project?->name ?: ''),
                'revenue' => (float) $invoices->sum('total'),
                'paid' => (float) $invoices->sum('amount_paid'),
            ])
            ->sortByDesc('revenue')
            ->values();

        $payments = Payment::query();
        $period->applyDate($payments, 'paid_on');
        $paymentRows = $payments->with(['client', 'invoice'])->latest('paid_on')->limit(50)->get();

        return [
            'metrics' => [
                ['label' => 'Revenue', 'value' => money($revenue), 'hint' => 'Billed invoices in period', 'icon' => 'currency'],
                ['label' => 'Expenses', 'value' => money($expenses), 'hint' => 'Approved and paid costs', 'icon' => 'folder'],
                ['label' => 'Profit', 'value' => money($profit), 'hint' => 'Collected minus expenses', 'icon' => 'chart'],
                ['label' => 'Outstanding', 'value' => money($outstanding), 'hint' => 'Current open balance', 'icon' => 'clock'],
                ['label' => 'Overdue', 'value' => money($overdue), 'hint' => 'Current past-due balance', 'icon' => 'alert'],
                ['label' => 'Payment collection', 'value' => money($collected), 'hint' => 'Receipts in period', 'icon' => 'check'],
            ],
            'charts' => [
                ['title' => 'Monthly revenue trend', 'items' => $revenueTrend],
                ['title' => 'Monthly expense trend', 'items' => $expenseTrend],
            ],
            'tables' => [
                [
                    'title' => 'Revenue by client',
                    'headers' => ['Client', 'Billed', 'Collected'],
                    'rows' => $byClient->map(fn (array $row) => [$row['label'], money($row['revenue']), money($row['paid'])])->all(),
                    'export' => $byClient->map(fn (array $row) => [$row['label'], $row['revenue'], $row['paid']])->all(),
                ],
                [
                    'title' => 'Revenue by project',
                    'headers' => ['Project', 'Billed', 'Collected'],
                    'rows' => $byProject->map(fn (array $row) => [$row['label'], money($row['revenue']), money($row['paid'])])->all(),
                    'export' => $byProject->map(fn (array $row) => [$row['label'], $row['revenue'], $row['paid']])->all(),
                ],
                [
                    'title' => 'Payments collected',
                    'headers' => ['Date', 'Client', 'Invoice', 'Amount'],
                    'rows' => $paymentRows->map(fn (Payment $payment) => [
                        $payment->paid_on?->format(settings('company.date_format', 'd M Y')),
                        $payment->client?->name,
                        $payment->invoice?->number,
                        money($payment->amount),
                    ])->all(),
                    'export' => $paymentRows->map(fn (Payment $payment) => [
                        $payment->paid_on?->toDateString(),
                        $payment->client?->name,
                        $payment->invoice?->number,
                        (float) $payment->amount,
                    ])->all(),
                ],
            ],
            'kpis' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $profit,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'collected' => $collected,
            ],
        ];
    }

    public function periodRevenue(ReportPeriod $period): float
    {
        return (float) $this->billedQuery($period)->sum('total');
    }

    public function periodCollected(ReportPeriod $period): float
    {
        $query = Payment::query();
        $period->applyDate($query, 'paid_on');

        return (float) $query->sum('amount');
    }

    public function periodExpenses(ReportPeriod $period): float
    {
        $query = Expense::query()->where('status', '!=', ExpenseStatus::Rejected->value);
        $period->applyDate($query, 'expense_date');

        return (float) $query->get()->sum(fn (Expense $expense) => $expense->total());
    }

    /**
     * @return Builder<Invoice>
     */
    protected function billedQuery(ReportPeriod $period)
    {
        $query = Invoice::query()->whereNotIn('status', [
            InvoiceStatus::Draft->value,
            InvoiceStatus::Cancelled->value,
        ]);

        return $period->applyDate($query, 'invoice_date');
    }
}
