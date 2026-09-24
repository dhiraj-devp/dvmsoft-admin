<?php

namespace App\Services;

use App\Automations\ClientPortalNotifier;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class InvoiceWorkflowService
{
    public function __construct(
        protected InvoiceBalanceService $balances,
        protected ClientPortalNotifier $portal,
    ) {}

    public function send(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft invoices can be sent.',
            ]);
        }

        if ($invoice->items()->doesntExist()) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one line item before sending.',
            ]);
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ])->save();

        $invoice = $this->balances->refresh($invoice->fresh());

        $invoice->loadMissing('client');
        $this->portal->notify(
            $invoice->client,
            'portal.activity',
            'invoice_sent',
            'New invoice',
            $invoice->number.' is ready to view.',
            $invoice->client ? route('client.invoices.show', $invoice) : null,
            ['invoice_id' => $invoice->id],
            $invoice,
            'sent',
        );

        return $invoice;
    }

    public function cancel(Invoice $invoice, User $actor): Invoice
    {
        if ((float) $invoice->amount_paid > 0) {
            throw ValidationException::withMessages([
                'status' => 'Invoices with payments cannot be cancelled.',
            ]);
        }

        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue], true)) {
            throw ValidationException::withMessages([
                'status' => 'This invoice cannot be cancelled.',
            ]);
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Cancelled,
            'cancelled_at' => now(),
            'amount_paid' => 0,
            'balance' => $invoice->total,
        ])->save();

        return $invoice->fresh();
    }
}
