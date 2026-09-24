<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Collection;

class TicketNotificationService
{
    public function notify(Ticket $ticket, string $event, ?User $actor = null, ?TicketMessage $message = null): void
    {
        $ticket->loadMissing(['client', 'assignedTo', 'createdBy']);

        foreach ($this->recipients($ticket, $event, $actor, $message) as $user) {
            $user->notify(new TicketEventNotification($ticket, $event, $message));
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipients(Ticket $ticket, string $event, ?User $actor, ?TicketMessage $message): Collection
    {
        $ids = collect();

        if ($event === 'assigned' && $ticket->assigned_to_id) {
            $ids->push($ticket->assigned_to_id);
        } elseif (in_array($event, ['sla_approaching', 'sla_breached'], true)) {
            if ($ticket->assigned_to_id) {
                $ids->push($ticket->assigned_to_id);
            } else {
                $ids = $ids->merge($this->supportStaffIds());
            }
        } else {
            $ids->push($ticket->assigned_to_id);
            $ids->push($ticket->created_by_id);
            $ids = $ids->merge($this->supportStaffIds());
        }

        if ($message?->is_internal) {
            $allowed = User::query()
                ->whereIn('id', $ids->filter()->unique())
                ->get()
                ->filter(fn (User $user) => $user->hasPermission('tickets.internal_notes'))
                ->pluck('id');
            $ids = $allowed;
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $ids->filter()->unique())
            ->when($actor, fn ($query) => $query->where('id', '!=', $actor->id))
            ->get();
    }

    /**
     * @return Collection<int, string>
     */
    protected function supportStaffIds(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('is_super_admin', true)
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('name', 'support.dashboard.view'));
            })
            ->pluck('id');
    }
}
