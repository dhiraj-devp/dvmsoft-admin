<?php

namespace App\Notifications;

use App\Models\Document;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentEventNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(public Document $document, public string $event) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->body())
            ->action('Open document', url(route('documents.show', $this->document, false)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->body(),
            'document_id' => $this->document->id,
            'document_number' => $this->document->number,
            'event' => $this->event,
            'channel' => 'internal',
        ];
    }

    protected function title(): string
    {
        return match ($this->event) {
            'submitted' => 'Document submitted '.$this->document->number,
            'approved' => 'Document approved '.$this->document->number,
            'rejected' => 'Document rejected '.$this->document->number,
            'expiring' => 'Document expiring '.$this->document->number,
            'expired' => 'Document expired '.$this->document->number,
            default => 'Document update '.$this->document->number,
        };
    }

    protected function body(): string
    {
        return match ($this->event) {
            'submitted' => $this->document->number.' “'.$this->document->title.'” was submitted for review.',
            'approved' => $this->document->number.' “'.$this->document->title.'” was approved.',
            'rejected' => $this->document->number.' “'.$this->document->title.'” was sent back to draft.',
            'expiring' => $this->document->number.' “'.$this->document->title.'” expires on '.$this->document->expiry_date?->format(settings('company.date_format', 'd M Y')).'.',
            'expired' => $this->document->number.' “'.$this->document->title.'” expired on '.$this->document->expiry_date?->format(settings('company.date_format', 'd M Y')).'.',
            default => $this->document->number.' was updated.',
        };
    }
}
