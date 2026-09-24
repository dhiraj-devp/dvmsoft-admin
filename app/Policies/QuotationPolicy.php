<?php

namespace App\Policies;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.edit') && $quotation->status->isEditable();
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.delete') && $quotation->status === QuotationStatus::Draft;
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.send')
            && in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Sent], true);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.approve')
            && in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true);
    }

    public function convert(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.convert')
            && $quotation->status === QuotationStatus::Accepted;
    }

    public function createProject(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('projects.create')
            && $quotation->status === QuotationStatus::Converted;
    }
}
