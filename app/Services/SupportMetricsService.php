<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Ticket;

class SupportMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $userId = null): array
    {
        $open = Ticket::query()->where('status', TicketStatus::Open->value)->count();
        $inProgress = Ticket::query()->where('status', TicketStatus::InProgress->value)->count();
        $waiting = Ticket::query()->where('status', TicketStatus::WaitingForClient->value)->count();
        $breached = Ticket::query()->breached()->count();
        $resolvedToday = Ticket::query()
            ->where('status', TicketStatus::Resolved->value)
            ->whereDate('resolved_at', now()->toDateString())
            ->count();

        $byPriority = collect(TicketPriority::cases())->map(fn (TicketPriority $priority) => [
            'label' => $priority->label(),
            'value' => Ticket::query()->where('priority', $priority->value)->count(),
        ]);

        $byStatus = collect(TicketStatus::cases())->map(fn (TicketStatus $status) => [
            'label' => $status->label(),
            'value' => Ticket::query()->where('status', $status->value)->count(),
        ]);

        return [
            'metrics' => [
                ['label' => 'Open tickets', 'value' => $open, 'hint' => 'Awaiting pickup', 'icon' => 'lifebuoy'],
                ['label' => 'In progress', 'value' => $inProgress, 'hint' => 'Being worked', 'icon' => 'clock'],
                ['label' => 'Waiting for client', 'value' => $waiting, 'hint' => 'Paused on the client', 'icon' => 'users'],
                ['label' => 'Overdue / SLA breached', 'value' => $breached, 'hint' => 'Past the SLA deadline', 'icon' => 'shield'],
                ['label' => 'Resolved today', 'value' => $resolvedToday, 'hint' => 'Closed the loop today', 'icon' => 'check'],
            ],
            'byPriority' => $byPriority,
            'byStatus' => $byStatus,
            'mine' => $userId
                ? Ticket::query()
                    ->with(['client', 'assignedTo'])
                    ->where('assigned_to_id', $userId)
                    ->open()
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            'recentActivity' => AuditLog::query()
                ->with('user')
                ->where('module', 'tickets')
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ];
    }
}
