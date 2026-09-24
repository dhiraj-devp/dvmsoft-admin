<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class InvoiceBalanceService
{
    public function refresh(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Cancelled) {
            return $invoice;
        }

        $paid = round((float) $invoice->payments()->sum('amount'), 2);
        $total = round((float) $invoice->total, 2);
        $balance = round(max(0, $total - $paid), 2);

        $status = $invoice->status;
        $paidAt = $invoice->paid_at;

        if ($paid >= $total && $total > 0) {
            $status = InvoiceStatus::Paid;
            $paidAt = $paidAt ?? now();
        } elseif ($paid > 0) {
            $status = InvoiceStatus::PartiallyPaid;
            $paidAt = null;
        } elseif ($invoice->status !== InvoiceStatus::Draft) {
            $due = $invoice->due_date;
            $status = $due && $due->lt(now()->startOfDay())
                ? InvoiceStatus::Overdue
                : InvoiceStatus::Sent;
            $paidAt = null;
        }

        $invoice->forceFill([
            'amount_paid' => $paid,
            'balance' => $balance,
            'status' => $status,
            'paid_at' => $paidAt,
        ])->save();

        return $invoice->fresh();
    }

    public function markOverdueInvoices(): int
    {
        $count = 0;

        Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::PartiallyPaid->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('balance', '>', 0)
            ->each(function (Invoice $invoice) use (&$count): void {
                $this->refresh($invoice);
                $count++;
            });

        return $count;
    }
}
