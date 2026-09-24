<?php

namespace App\Services\Ai;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;

class TicketAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(Ticket $ticket, User $user): array
    {
        $ticket->loadMissing(['client', 'project', 'category', 'assignedTo', 'messages']);

        return $this->generate('ticket', $this->context($ticket, $user), $ticket);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(Ticket $ticket, User $user): array
    {
        $includeInternal = $user->hasPermission('tickets.internal_notes');

        $public = $ticket->messages->where('is_internal', false)->take(20);
        $internal = $includeInternal
            ? $ticket->messages->where('is_internal', true)->take(10)
            : collect();

        return [
            'ticket' => [
                'number' => $ticket->number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'category' => $ticket->category?->name,
                'client' => $ticket->client?->name,
                'project' => $ticket->project?->number,
                'project_name' => $ticket->project?->name,
                'assigned_to' => $ticket->assignedTo?->name,
                'sla_due_at' => optional($ticket->sla_due_at)?->toDateTimeString(),
                'sla_breached' => $ticket->isSlaBreached(),
            ],
            'public_messages' => $public->map(fn (TicketMessage $message) => [
                'author' => $message->displayAuthorName(),
                'body' => $message->body,
                'at' => optional($message->created_at)?->toDateTimeString(),
            ])->values()->all(),
            'internal_notes' => $internal->map(fn (TicketMessage $message) => [
                'author' => $message->displayAuthorName(),
                'body' => $message->body,
                'at' => optional($message->created_at)?->toDateTimeString(),
            ])->values()->all(),
            'rules' => [
                'draft_reply_must_ignore_internal_notes' => true,
                'never_send_the_reply' => true,
            ],
        ];
    }
}
