<?php

namespace App\Livewire\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Conversation extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $ticketId = '';

    public string $body = '';

    public bool $internal = false;

    public string $assignedToId = '';

    public string $status = '';

    public string $resolution = '';

    public $upload = null;

    public function mount(string $ticketId): void
    {
        $ticket = Ticket::query()->findOrFail($ticketId);
        $this->authorize('view', $ticket);
        $this->ticketId = $ticketId;
        $this->assignedToId = $ticket->assigned_to_id ?? '';
        $this->status = $ticket->status->value;
        $this->resolution = $ticket->resolution ?? '';
    }

    #[On('ai-draft-reply')]
    public function fillFromAi(string $body): void
    {
        $this->body = $body;
        $this->internal = false;
    }

    public function reply(TicketWorkflowService $workflow): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $this->authorize('reply', $ticket);

        if ($this->internal) {
            $this->authorize('internalNotes', Ticket::class);
        }

        $this->validate([
            'body' => ['required', 'string', 'max:10000'],
            'upload' => ['nullable', 'file', 'max:20480'],
        ]);

        $file = $this->upload instanceof TemporaryUploadedFile ? $this->upload : null;
        $workflow->reply($ticket, request()->user(), $this->body, $this->internal, $file);

        $this->reset('body', 'internal', 'upload');
        $this->dispatch('notify', type: 'success', message: 'Reply posted.');
    }

    public function assign(TicketWorkflowService $workflow): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $this->authorize('assign', $ticket);
        $workflow->assign($ticket, $this->assignedToId ?: null, request()->user());
        $this->status = $ticket->fresh()->status->value;
        $this->dispatch('notify', type: 'success', message: 'Assignee updated.');
    }

    public function changeStatus(TicketWorkflowService $workflow): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $this->authorize('update', $ticket);
        $workflow->setStatus($ticket, TicketStatus::from($this->status));
        $this->dispatch('notify', type: 'success', message: 'Status updated.');
    }

    public function resolve(TicketWorkflowService $workflow): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $this->authorize('resolve', $ticket);
        $this->validate([
            'resolution' => ['required', 'string', 'max:5000'],
        ]);
        $workflow->resolve($ticket, request()->user(), $this->resolution);
        $this->status = TicketStatus::Resolved->value;
        $this->dispatch('notify', type: 'success', message: 'Ticket resolved.');
    }

    public function close(TicketWorkflowService $workflow): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $this->authorize('close', $ticket);
        $workflow->close($ticket, request()->user());
        $this->status = TicketStatus::Closed->value;
        $this->dispatch('notify', type: 'success', message: 'Ticket closed.');
    }

    public function render(): View
    {
        $ticket = Ticket::query()->with(['assignedTo'])->findOrFail($this->ticketId);
        $user = request()->user();

        $messages = TicketMessage::query()
            ->with(['author', 'clientUser', 'attachments'])
            ->where('ticket_id', $this->ticketId)
            ->visibleTo($user)
            ->oldest()
            ->get();

        return view('livewire.tickets.conversation', [
            'ticket' => $ticket,
            'messages' => $messages,
            'assignees' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'openStatuses' => collect(TicketStatus::cases())->filter(fn (TicketStatus $status) => $status->isOpen()),
            'canSeeInternal' => $user->hasPermission('tickets.internal_notes'),
        ]);
    }
}
