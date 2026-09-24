<?php

namespace App\Policies;

use App\Models\TicketAttachment;
use App\Models\User;

class TicketAttachmentPolicy
{
    public function view(User $user, TicketAttachment $attachment): bool
    {
        if (! $user->hasPermission('tickets.view')) {
            return false;
        }

        $attachment->loadMissing('message');

        if ($attachment->message?->is_internal && ! $user->hasPermission('tickets.internal_notes')) {
            return false;
        }

        return true;
    }
}
