<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TicketPriority;
use App\Models\TicketCategory;
use App\Services\AuditLogger;
use App\Services\ClientPortal\ClientTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends PortalController
{
    public function index(Request $request): View
    {
        $user = $this->portalUser();
        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();

        $tickets = $this->access()->tickets($user)
            ->with('project')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search): void {
                $inner->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('number', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.tickets.index', compact('tickets', 'status', 'search'));
    }

    public function create(): View
    {
        $user = $this->portalUser();

        return view('portal.tickets.create', [
            'projects' => $this->access()->projects($user)->orderBy('name')->get(['id', 'name', 'number']),
            'categories' => TicketCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(Request $request, ClientTicketService $tickets, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();

        $validated = $request->validate([
            'project_id' => ['nullable', 'ulid', Rule::exists('projects', 'id')->where('client_id', $user->client_id)],
            'category_id' => ['nullable', 'ulid', 'exists:ticket_categories,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $ticket = $tickets->create($user, $validated, $request->file('attachment'));

        $audit->record(
            action: 'created',
            module: 'client_portal',
            auditable: $ticket,
            newValues: array_merge($user->auditActorValues(), [
                'ticket_id' => $ticket->id,
                'number' => $ticket->number,
            ]),
            user: $user,
        );

        return redirect()
            ->route('client.tickets.show', $ticket)
            ->with('status', 'Ticket submitted.');
    }

    public function show(string $ticket): View
    {
        $user = $this->portalUser();
        $ticket = $this->access()->ticket($user, $ticket)->load(['project', 'category']);

        $messages = $ticket->messages()
            ->visibleToClient()
            ->with(['attachments', 'clientUser', 'author'])
            ->get();

        return view('portal.tickets.show', compact('ticket', 'messages'));
    }

    public function reply(Request $request, string $ticket, ClientTicketService $tickets, AuditLogger $audit): RedirectResponse
    {
        $user = $this->portalUser();
        $ticket = $this->access()->ticket($user, $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:20480'],
        ]);

        $message = $tickets->reply($user, $ticket, $validated['body'], $request->file('attachment'));

        $audit->record(
            action: 'replied',
            module: 'client_portal',
            auditable: $ticket,
            newValues: array_merge($user->auditActorValues(), [
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
            ]),
            user: $user,
        );

        return back()->with('status', 'Reply sent.');
    }

    public function downloadAttachment(string $ticket, string $message, string $attachment, AuditLogger $audit): StreamedResponse
    {
        $user = $this->portalUser();
        $ticket = $this->access()->ticket($user, $ticket);
        $file = $this->access()->ticketAttachment($user, $ticket, $message, $attachment);

        $disk = $file->disk ?: 'support';

        abort_unless(in_array($disk, ['support', 'local'], true), 404);
        abort_unless(Storage::disk($disk)->exists($file->path), 404);

        $audit->record(
            action: 'downloaded',
            module: 'client_portal',
            auditable: $ticket,
            newValues: array_merge($user->auditActorValues(), [
                'file' => $file->original_name,
            ]),
            user: $user,
        );

        return Storage::disk($disk)->download($file->path, $file->original_name);
    }
}
