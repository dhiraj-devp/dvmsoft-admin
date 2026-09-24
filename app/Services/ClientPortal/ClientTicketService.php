<?php

namespace App\Services\ClientPortal;

use App\Automations\ClientPortalNotifier;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ClientUser;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Services\SequentialNumberGenerator;
use App\Services\TicketNotificationService;
use App\Services\TicketSlaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientTicketService
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
    public function create(ClientUser $actor, array $attributes, ?UploadedFile $file = null): Ticket
    {
        $this->assertProjectBelongsToClient($actor, $attributes['project_id'] ?? null);

        return DB::transaction(function () use ($actor, $attributes, $file) {
            $priority = TicketPriority::from($attributes['priority']);

            $ticket = Ticket::query()->create([
                'number' => $this->numbers->nextTicket(),
                'client_id' => $actor->client_id,
                'project_id' => $attributes['project_id'] ?? null,
                'category_id' => $attributes['category_id'] ?? null,
                'assigned_to_id' => null,
                'created_by_id' => null,
                'client_user_id' => $actor->id,
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
                'priority' => $priority,
                'status' => TicketStatus::Open,
            ]);

            $this->sla->apply($ticket);
            $ticket->save();

            $opening = $this->addMessage($ticket, $actor, (string) $attributes['description'], $file);

            $this->notifications->notify($ticket, 'created', null, $opening);
            $this->notifyPortal($ticket->fresh('client'), $actor, 'created', 'Support ticket opened', $ticket->number.': '.$ticket->subject);

            return $ticket->fresh(['client', 'project', 'category']);
        });
    }

    public function reply(ClientUser $actor, Ticket $ticket, string $body, ?UploadedFile $file = null): TicketMessage
    {
        abort_unless($ticket->client_id === $actor->client_id, 404);

        if ($ticket->status === TicketStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Closed tickets cannot receive new replies.',
            ]);
        }

        $message = $this->addMessage($ticket, $actor, $body, $file);

        if ($ticket->status === TicketStatus::WaitingForClient) {
            $ticket->update(['status' => TicketStatus::InProgress]);
        }

        $this->notifications->notify($ticket->fresh(['client', 'assignedTo', 'createdBy']), 'reply', null, $message);
        $this->notifyPortal($ticket->fresh('client'), $actor, 'reply', 'New ticket reply', 'A reply was posted on '.$ticket->number.'.', $message->id);

        return $message;
    }

    protected function addMessage(Ticket $ticket, ClientUser $actor, string $body, ?UploadedFile $file = null): TicketMessage
    {
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'author_id' => null,
            'client_user_id' => $actor->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        if ($file) {
            $path = $file->store('tickets/'.$ticket->id, 'support');
            TicketAttachment::query()->create([
                'ticket_message_id' => $message->id,
                'uploaded_by_id' => null,
                'client_user_id' => $actor->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'support',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return $message->fresh('attachments');
    }

    protected function assertProjectBelongsToClient(ClientUser $actor, mixed $projectId): void
    {
        if (blank($projectId)) {
            return;
        }

        $project = Project::query()->find($projectId);

        if (! $project || $project->client_id !== $actor->client_id) {
            throw ValidationException::withMessages([
                'project_id' => 'The project must belong to your account.',
            ]);
        }
    }

    protected function notifyPortal(Ticket $ticket, ClientUser $except, string $event, string $title, string $message, ?string $occurrenceSuffix = null): void
    {
        $this->portal->notify(
            $ticket->client,
            'portal.ticket_activity',
            $event,
            $title,
            $message,
            route('client.tickets.show', $ticket),
            ['ticket_id' => $ticket->id],
            $ticket,
            $event.($occurrenceSuffix ? ':'.$occurrenceSuffix : ''),
            $except,
        );
    }
}
