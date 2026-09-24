<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\TicketSlaRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TicketSlaService
{
    public function ruleFor(TicketPriority $priority): ?TicketSlaRule
    {
        return TicketSlaRule::query()->where('priority', $priority->value)->first();
    }

    public function dueAt(TicketPriority $priority, ?Carbon $from = null): Carbon
    {
        $from ??= now();
        $hours = $this->ruleFor($priority)?->hours
            ?? (int) (config('support.sla.'.$priority->value.'.hours') ?: 24);

        return $from->copy()->addHours(max(1, $hours));
    }

    public function apply(Ticket $ticket): Ticket
    {
        $ticket->sla_due_at = $this->dueAt($ticket->priority, $ticket->created_at ?? now());

        if ($ticket->isSlaBreached() && ! $ticket->sla_breached_at) {
            $ticket->sla_breached_at = $ticket->sla_due_at;
        }

        if (! $ticket->isSlaBreached()) {
            $ticket->sla_breached_at = null;
        }

        return $ticket;
    }

    public function warningAt(Ticket $ticket): ?Carbon
    {
        if (! $ticket->sla_due_at) {
            return null;
        }

        $hours = $this->ruleFor($ticket->priority)?->warning_hours
            ?? (int) (config('support.sla.'.$ticket->priority->value.'.warning_hours') ?: 1);

        return $ticket->sla_due_at->copy()->subHours(max(0, $hours));
    }

    public function isApproaching(Ticket $ticket): bool
    {
        if (! $ticket->status->isOpen() || ! $ticket->sla_due_at) {
            return false;
        }

        $warning = $this->warningAt($ticket);

        if (! $warning) {
            return false;
        }

        return now()->gte($warning) && now()->lt($ticket->sla_due_at);
    }

    public function notifyApproaching(TicketNotificationService $notifications): int
    {
        $sent = 0;

        foreach ($this->openTickets() as $ticket) {
            if ($ticket->isSlaBreached()) {
                continue;
            }

            if ($this->isApproaching($ticket) && ! $ticket->sla_notified_approaching_at) {
                $ticket->forceFill(['sla_notified_approaching_at' => now()])->saveQuietly();
                $notifications->notify($ticket, 'sla_approaching');
                $sent++;
            }
        }

        return $sent;
    }

    public function notifyBreached(TicketNotificationService $notifications): int
    {
        $sent = 0;

        foreach ($this->openTickets() as $ticket) {
            if ($ticket->isSlaBreached() && ! $ticket->sla_notified_breached_at) {
                $ticket->forceFill([
                    'sla_breached_at' => $ticket->sla_breached_at ?? now(),
                    'sla_notified_breached_at' => now(),
                ])->saveQuietly();
                $notifications->notify($ticket, 'sla_breached');
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @return Collection<int, Ticket>
     */
    protected function openTickets()
    {
        return Ticket::query()
            ->with(['client', 'assignedTo', 'createdBy'])
            ->open()
            ->whereNotNull('sla_due_at')
            ->get();
    }
}
