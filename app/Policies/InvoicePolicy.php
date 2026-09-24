<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.edit') && $invoice->status->isEditable();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete') && $invoice->status === InvoiceStatus::Draft;
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.send') && $invoice->status === InvoiceStatus::Draft;
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.edit')
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue], true)
            && (float) $invoice->amount_paid === 0.0;
    }
}
