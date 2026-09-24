<?php

namespace App\Services\Reports;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\ReportPeriod;

class SupportReportService
{
    /**
     * @return array<string, mixed>
     */
    public function build(ReportPeriod $period): array
    {
        $created = Ticket::query();
        $period->apply($created, 'created_at');

        $byStatus = collect(TicketStatus::cases())->map(fn (TicketStatus $status) => [
            'label' => $status->label(),
            'value' => (clone $created)->where('status', $status->value)->count(),
        ])->all();

        $byPriority = collect(TicketPriority::cases())->map(fn (TicketPriority $priority) => [
            'label' => $priority->label(),
            'value' => (clone $created)->where('priority', $priority->value)->count(),
        ])->all();

        $breachesCurrent = Ticket::query()->breached()->count();
        $breachesInPeriod = Ticket::query()->whereNotNull('sla_breached_at');
        $period->apply($breachesInPeriod, 'sla_breached_at');
        $breachPeriodCount = $breachesInPeriod->count();

        $resolved = Ticket::query()->whereNotNull('resolved_at');
        $period->apply($resolved, 'resolved_at');
        $resolvedTickets = $resolved->get(['created_at', 'resolved_at']);
        $avgHours = $resolvedTickets->isEmpty()
            ? 0.0
            : round($resolvedTickets->avg(function (Ticket $ticket) {
                return $ticket->created_at->diffInMinutes($ticket->resolved_at) / 60;
            }), 1);

        $byCategory = TicketCategory::query()
            ->orderBy('name')
            ->get()
            ->map(fn (TicketCategory $category) => [
                'label' => $category->name,
                'value' => (clone $created)->where('category_id', $category->id)->count(),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values()
            ->all();

        $uncategorised = (clone $created)->whereNull('category_id')->count();
        if ($uncategorised > 0) {
            $byCategory[] = ['label' => 'Uncategorised', 'value' => $uncategorised];
        }

        $byAssignee = User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($created) {
                $count = (clone $created)->where('assigned_to_id', $user->id)->count();

                return $count > 0 ? ['label' => $user->name, 'value' => $count] : null;
            })
            ->filter()
            ->values()
            ->all();

        $unassigned = (clone $created)->whereNull('assigned_to_id')->count();
        if ($unassigned > 0) {
            $byAssignee[] = ['label' => 'Unassigned', 'value' => $unassigned];
        }

        $openNow = Ticket::query()->open()->count();

        return [
            'metrics' => [
                ['label' => 'Tickets opened', 'value' => (clone $created)->count(), 'hint' => 'Created in period', 'icon' => 'lifebuoy'],
                ['label' => 'Open tickets', 'value' => $openNow, 'hint' => 'Current queue', 'icon' => 'clock'],
                ['label' => 'SLA breaches', 'value' => $breachesCurrent, 'hint' => $breachPeriodCount.' breached in period', 'icon' => 'shield'],
                ['label' => 'Avg. resolution time', 'value' => $avgHours.'h', 'hint' => $resolvedTickets->count().' resolved in period', 'icon' => 'check'],
            ],
            'charts' => [
                ['title' => 'Tickets by status', 'items' => $this->withDisplay($byStatus)],
                ['title' => 'Tickets by priority', 'items' => $this->withDisplay($byPriority)],
                ['title' => 'Tickets by category', 'items' => $this->withDisplay($byCategory)],
                ['title' => 'Tickets by assignee', 'items' => $this->withDisplay($byAssignee)],
            ],
            'tables' => [
                [
                    'title' => 'Tickets by status',
                    'headers' => ['Status', 'Count'],
                    'rows' => collect($byStatus)->map(fn (array $row) => [$row['label'], $row['value']])->all(),
                    'export' => collect($byStatus)->map(fn (array $row) => [$row['label'], $row['value']])->all(),
                ],
                [
                    'title' => 'Tickets by assignee',
                    'headers' => ['Assignee', 'Tickets'],
                    'rows' => collect($byAssignee)->map(fn (array $row) => [$row['label'], $row['value']])->all(),
                    'export' => collect($byAssignee)->map(fn (array $row) => [$row['label'], $row['value']])->all(),
                ],
            ],
            'kpis' => [
                'open_tickets' => $openNow,
                'tickets_opened' => (clone $created)->count(),
            ],
        ];
    }

    /**
     * @param  list<array{label: string, value: int|float}>  $items
     * @return list<array{label: string, value: int|float, display: string}>
     */
    protected function withDisplay(array $items): array
    {
        return collect($items)->map(fn (array $item) => $item + ['display' => (string) $item['value']])->all();
    }
}
