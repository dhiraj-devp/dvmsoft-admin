<?php

namespace App\Services;

use App\Automations\ClientPortalNotifier;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    public function __construct(
        protected SequentialNumberGenerator $numbers,
        protected TicketSlaService $sla,
        protected TicketNotificationService $notifications,
        protected ClientPortalNotifier $portal,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $actor, ?UploadedFile $file = null): Ticket
    {
        $this->assertProjectBelongsToClient($attributes);

        return DB::transaction(function () use ($attributes, $actor, $file) {
            $priority = TicketPriority::from($attributes['priority']);
            $assignedId = $attributes['assigned_to_id'] ?: null;

            $ticket = Ticket::query()->create([
                'number' => $this->numbers->nextTicket(),
                'client_id' => $attributes['client_id'],
                'project_id' => $attributes['project_id'] ?: null,
                'category_id' => $attributes['category_id'] ?: null,
                'assigned_to_id' => $assignedId,
                'created_by_id' => $actor->id,
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'priority' => $priority,
                'status' => $assignedId ? TicketStatus::InProgress : TicketStatus::Open,
            ]);

            $this->sla->apply($ticket);
            $ticket->save();

            $opening = $this->addMessage($ticket, $actor, (string) $attributes['description'], false, $file);

            $this->notifications->notify($ticket, 'created', $actor, $opening);

            if ($assignedId) {
                $this->notifications->notify($ticket->fresh(['assignedTo', 'client']), 'assigned', $actor);
            }

            $this->notifyPortal($ticket->fresh('client'), 'created', 'Support ticket opened', $ticket->number.': '.$ticket->subject);

            return $ticket->fresh(['client', 'project', 'category', 'assignedTo', 'createdBy']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Ticket $ticket, array $attributes): Ticket
    {
        $this->assertProjectBelongsToClient($attributes);

        $priority = TicketPriority::from($attributes['priority']);
        $priorityChanged = $ticket->priority !== $priority;

        $ticket->update([
            'client_id' => $attributes['client_id'],
            'project_id' => $attributes['project_id'] ?: null,
            'category_id' => $attributes['category_id'] ?: null,
            'subject' => $attributes['subject'],
            'description' => $attributes['description'],
            'priority' => $priority,
        ]);

        $ticket = $ticket->fresh();

        if ($priorityChanged && $ticket->status->isOpen()) {
            $this->sla->apply($ticket);
            $ticket->forceFill([
                'sla_notified_approaching_at' => null,
                'sla_notified_breached_at' => null,
            ])->save();
        }

        return $ticket;
    }

    public function assign(Ticket $ticket, ?string $assigneeId, User $actor): Ticket
    {
        $previous = $ticket->assigned_to_id;

        $ticket->update([
            'assigned_to_id' => $assigneeId ?: null,
            'status' => $assigneeId && $ticket->status === TicketStatus::Open
                ? TicketStatus::InProgress
                : $ticket->status,
        ]);

        if ($assigneeId && $assigneeId !== $previous) {
            $this->notifications->notify($ticket->fresh(['assignedTo', 'client']), 'assigned', $actor);
        }

        return $ticket->fresh();
    }

    public function setStatus(Ticket $ticket, TicketStatus $status): Ticket
    {
        if ($status === TicketStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Use close to mark a ticket closed.',
            ]);
        }

        if ($status === TicketStatus::Resolved) {
            throw ValidationException::withMessages([
                'status' => 'Use resolve to mark a ticket resolved.',
            ]);
        }

        $ticket->update(['status' => $status]);

        return $ticket->fresh();
    }

    public function reply(Ticket $ticket, User $actor, string $body, bool $internal = false, ?UploadedFile $file = null): TicketMessage
    {
        if ($ticket->status === TicketStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Closed tickets cannot receive new replies.',
            ]);
        }

        $message = $this->addMessage($ticket, $actor, $body, $internal, $file);

        if (! $internal && $ticket->status === TicketStatus::WaitingForClient) {
            $ticket->update(['status' => TicketStatus::InProgress]);
        }

        $this->notifications->notify($ticket->fresh(['client', 'assignedTo', 'createdBy']), 'reply', $actor, $message);

        if (! $internal) {
            $this->notifyPortal(
                $ticket->fresh('client'),
                'reply',
                'New ticket reply',
                'A reply was posted on '.$ticket->number.'.',
                $message->id,
            );
        }

        return $message;
    }

    public function resolve(Ticket $ticket, User $actor, string $resolution): Ticket
    {
        if ($ticket->status === TicketStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Closed tickets cannot be resolved.',
            ]);
        }

        $ticket->update([
            'status' => TicketStatus::Resolved,
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);

        if ($ticket->fresh()->isSlaBreached() && ! $ticket->sla_breached_at) {
            $ticket->forceFill(['sla_breached_at' => $ticket->sla_due_at])->save();
        }

        $this->notifications->notify($ticket->fresh(['client', 'assignedTo', 'createdBy']), 'resolved', $actor);
        $this->notifyPortal($ticket->fresh('client'), 'resolved', 'Ticket resolved', $ticket->number.' was marked resolved.');

        return $ticket->fresh();
    }

    public function close(Ticket $ticket, User $actor): Ticket
    {
        if ($ticket->status !== TicketStatus::Resolved) {
            throw ValidationException::withMessages([
                'status' => 'Only resolved tickets can be closed.',
            ]);
        }

        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);

        return $ticket->fresh();
    }

    protected function addMessage(Ticket $ticket, User $actor, string $body, bool $internal, ?UploadedFile $file = null): TicketMessage
    {
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $actor->id,
            'body' => $body,
            'is_internal' => $internal,
        ]);

        if ($file) {
            $path = $file->store('tickets/'.$ticket->id, 'support');
            TicketAttachment::query()->create([
                'ticket_message_id' => $message->id,
                'uploaded_by_id' => $actor->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'support',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $message->fresh('attachments');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function assertProjectBelongsToClient(array $attributes): void
    {
        if (blank($attributes['project_id'] ?? null)) {
            return;
        }

        $project = Project::query()->find($attributes['project_id']);

        if (! $project || $project->client_id !== $attributes['client_id']) {
            throw ValidationException::withMessages([
                'project_id' => 'The project must belong to the selected client.',
            ]);
        }
    }

    protected function notifyPortal(Ticket $ticket, string $event, string $title, string $message, ?string $occurrenceSuffix = null): void
    {
        $this->portal->notify(
            $ticket->client,
            'portal.ticket_activity',
            $event,
            $title,
            $message,
            $ticket->client ? route('client.tickets.show', $ticket) : null,
            ['ticket_id' => $ticket->id],
            $ticket,
            $event.($occurrenceSuffix ? ':'.$occurrenceSuffix : ''),
        );
    }
}
