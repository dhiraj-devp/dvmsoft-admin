<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.edit') && $ticket->status !== TicketStatus::Closed;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.delete');
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.assign') && $ticket->status !== TicketStatus::Closed;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.reply') && $ticket->status !== TicketStatus::Closed;
    }

    public function internalNotes(User $user): bool
    {
        return $user->hasPermission('tickets.internal_notes');
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.resolve') && $ticket->status->isOpen();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('tickets.close') && $ticket->status === TicketStatus::Resolved;
    }
}
