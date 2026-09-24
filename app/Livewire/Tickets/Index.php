<?php

namespace App\Livewire\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $priority = '';

    public string $mine = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);
        $this->mine = request()->boolean('mine') ? '1' : '';
    }

    public function updatingSearch(): void
    {
        $this->resetPage('ticketsPage');
    }

    #[On('confirmed-delete-ticket')]
    public function delete(string $id): void
    {
        $ticket = Ticket::query()->findOrFail($id);
        $this->authorize('delete', $ticket);
        $ticket->delete();
        $this->dispatch('notify', type: 'success', message: 'Ticket removed.');
    }

    public function render(): View
    {
        $tickets = Ticket::query()
            ->with(['client', 'project', 'category', 'assignedTo'])
            ->when($this->search, function ($query) {
                $query->where(function ($nested) {
                    $nested->where('number', 'like', '%'.$this->search.'%')
                        ->orWhere('subject', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($clients) => $clients->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->priority, fn ($query) => $query->where('priority', $this->priority))
            ->when($this->mine === '1', fn ($query) => $query->where('assigned_to_id', auth()->id()))
            ->latest()
            ->paginate(12, pageName: 'ticketsPage');

        return view('livewire.tickets.index', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }
}
