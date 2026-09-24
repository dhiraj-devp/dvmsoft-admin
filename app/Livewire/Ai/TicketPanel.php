<?php

namespace App\Livewire\Ai;

use App\Models\Ticket;
use App\Services\Ai\TicketAssistant;
use Illuminate\Contracts\View\View;

class TicketPanel extends AiPanel
{
    public string $ticketId = '';

    public bool $canFillReply = false;

    public function mount(string $ticketId): void
    {
        $this->ticketId = $ticketId;
        $this->authorize($this->permission());
        $ticket = Ticket::query()->findOrFail($ticketId);
        $this->authorize('view', $ticket);
        $this->canFillReply = request()->user()->can('reply', $ticket);
    }

    public function draftReply(TicketAssistant $assistant): void
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        $user = request()->user();

        $this->generateWith(fn () => $assistant->analyze($ticket, $user), $ticket);
    }

    public function fillReply(): void
    {
        $this->authorize($this->permission());
        $this->authorize('reply', Ticket::query()->findOrFail($this->ticketId));

        $draft = (string) ($this->result['draft_reply'] ?? '');

        if ($draft === '') {
            return;
        }

        $this->dispatch('ai-draft-reply', body: $draft);
        $this->applied = true;
        $this->dispatch('notify', type: 'success', message: 'Draft filled into the reply box. It was not sent.');
    }

    public function render(): View
    {
        return view('livewire.ai.ticket-panel');
    }

    protected function feature(): string
    {
        return 'ticket';
    }

    protected function permission(): string
    {
        return 'ai.tickets.use';
    }
}
