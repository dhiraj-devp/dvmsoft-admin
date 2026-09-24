<?php

namespace App\Livewire\Tickets;

use App\Enums\TicketPriority;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?string $ticketId = null;

    public array $form = [];

    public $upload = null;

    public function mount(?Ticket $ticket = null): void
    {
        if ($ticket?->exists) {
            $this->authorize('update', $ticket);
            $this->ticketId = $ticket->id;
            $this->form = [
                'client_id' => $ticket->client_id,
                'project_id' => $ticket->project_id ?? '',
                'category_id' => $ticket->category_id ?? '',
                'assigned_to_id' => $ticket->assigned_to_id ?? '',
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority->value,
            ];
        } else {
            $this->authorize('create', Ticket::class);
            $this->form = [
                'client_id' => request('client_id', ''),
                'project_id' => request('project_id', ''),
                'category_id' => '',
                'assigned_to_id' => '',
                'subject' => '',
                'description' => '',
                'priority' => TicketPriority::Normal->value,
            ];
        }
    }

    public function updatedFormClientId(): void
    {
        $this->form['project_id'] = '';
    }

    public function save(TicketWorkflowService $workflow): mixed
    {
        $ticket = $this->ticketId ? Ticket::query()->findOrFail($this->ticketId) : null;
        $ticket ? $this->authorize('update', $ticket) : $this->authorize('create', Ticket::class);

        $this->validate([
            'form.client_id' => ['required', 'ulid', 'exists:clients,id'],
            'form.project_id' => ['nullable', 'ulid', 'exists:projects,id'],
            'form.category_id' => ['nullable', 'ulid', 'exists:ticket_categories,id'],
            'form.assigned_to_id' => ['nullable', 'ulid', 'exists:users,id'],
            'form.subject' => ['required', 'string', 'max:255'],
            'form.description' => ['required', 'string', 'max:10000'],
            'form.priority' => ['required', Rule::enum(TicketPriority::class)],
            'upload' => ['nullable', 'file', 'max:20480'],
        ]);

        $file = $this->upload instanceof TemporaryUploadedFile ? $this->upload : null;

        if ($ticket) {
            $ticket = $workflow->update($ticket, $this->form);
            session()->flash('status', 'Ticket updated.');
        } else {
            $ticket = $workflow->create($this->form, request()->user(), $file);
            session()->flash('status', 'Ticket opened.');
        }

        return redirect()->route('tickets.show', $ticket);
    }

    public function render(): View
    {
        $clientId = $this->form['client_id'] ?? '';

        return view('livewire.tickets.form', [
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'projects' => $clientId
                ? Project::query()->where('client_id', $clientId)->orderBy('name')->get(['id', 'name', 'number', 'client_id'])
                : collect(),
            'categories' => TicketCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'assignees' => User::query()->active()->orderBy('name')->get(['id', 'name']),
            'priorities' => TicketPriority::cases(),
            'ticket' => $this->ticketId ? Ticket::query()->find($this->ticketId) : null,
        ]);
    }
}
