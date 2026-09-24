<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function download(Ticket $ticket, TicketMessage $message, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        abort_unless($message->ticket_id === $ticket->id, 404);
        abort_unless($attachment->ticket_message_id === $message->id, 404);
        abort_unless($attachment->disk === 'support' || $attachment->disk === 'local', 404);

        $disk = $attachment->disk ?: 'support';

        abort_unless(Storage::disk($disk)->exists($attachment->path), 404);

        return Storage::disk($disk)->download($attachment->path, $attachment->original_name);
    }
}
