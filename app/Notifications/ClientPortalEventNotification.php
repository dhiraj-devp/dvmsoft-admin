<?php

namespace App\Notifications;

use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientPortalEventNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public ?string $url = null,
        public array $payload = [],
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->url) {
            $mail->action('Open in Client Portal', url($this->url));
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'event' => $this->event,
            'payload' => $this->payload,
        ];
    }
}
