<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(public Invoice $invoice, public string $type) {}

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->type) {
            'overdue' => 'Overdue invoice '.$this->invoice->number,
            'outstanding' => 'Outstanding invoice '.$this->invoice->number,
            default => 'Upcoming invoice '.$this->invoice->number,
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($this->body())
            ->line('WhatsApp delivery is not enabled yet.')
            ->action('Open invoice', url(route('invoices.show', $this->invoice, false)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => match ($this->type) {
                'overdue' => 'Overdue invoice',
                'outstanding' => 'Outstanding invoice',
                default => 'Invoice due soon',
            },
            'message' => $this->body(),
            'invoice_id' => $this->invoice->id,
            'channel' => 'internal',
        ];
    }

    protected function body(): string
    {
        $client = $this->invoice->client?->name ?? 'client';

        if ($this->type === 'overdue') {
            return $this->invoice->number.' for '.$client.' is overdue. Balance '.money($this->invoice->balance).'.';
        }

        if ($this->type === 'outstanding') {
            return $this->invoice->number.' for '.$client.' still has an outstanding balance of '.money($this->invoice->balance).'.';
        }

        return $this->invoice->number.' for '.$client.' is due on '.$this->invoice->due_date?->format(settings('company.date_format', 'd M Y')).'.';
    }
}
