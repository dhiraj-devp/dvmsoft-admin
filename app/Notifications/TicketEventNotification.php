<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEventNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(
        public Ticket $ticket,
        public string $event,
        public ?TicketMessage $message = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->body())
            ->line('WhatsApp delivery is not enabled yet.')
            ->action('Open ticket', url(route('tickets.show', $this->ticket, false)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->body(),
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'event' => $this->event,
            'channel' => 'internal',
        ];
    }

    protected function title(): string
    {
        return match ($this->event) {
            'created' => 'Ticket created '.$this->ticket->number,
            'assigned' => 'Ticket assigned '.$this->ticket->number,
            'reply' => 'New reply on '.$this->ticket->number,
            'resolved' => 'Ticket resolved '.$this->ticket->number,
            'sla_approaching' => 'SLA approaching '.$this->ticket->number,
            'sla_breached' => 'SLA breached '.$this->ticket->number,
            default => 'Ticket update '.$this->ticket->number,
        };
    }

    protected function body(): string
    {
        $client = $this->ticket->client?->name ?? 'client';

        return match ($this->event) {
            'created' => $this->ticket->number.' was opened for '.$client.': '.$this->ticket->subject,
            'assigned' => $this->ticket->number.' was assigned to '.$this->ticket->assignedTo?->name.'.',
            'reply' => ($this->message?->author?->name ?? 'Someone').' replied on '.$this->ticket->number.'.',
            'resolved' => $this->ticket->number.' was marked resolved.',
            'sla_approaching' => $this->ticket->number.' is approaching its SLA deadline ('.$this->ticket->slaLabel().').',
            'sla_breached' => $this->ticket->number.' has breached its SLA deadline.',
            default => $this->ticket->number.' was updated.',
        };
    }
}
