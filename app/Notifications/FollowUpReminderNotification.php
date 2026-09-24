<?php

namespace App\Notifications;

use App\Models\FollowUp;
use App\Notifications\Concerns\ResolvesNotificationChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesNotificationChannels;

    public function __construct(public FollowUp $followUp) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Follow-up reminder')
            ->line('A '.$this->followUp->type->label().' follow-up is due for '.$this->followUp->subjectName().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Follow-up reminder',
            'message' => 'A '.$this->followUp->type->label().' follow-up is due for '.$this->followUp->subjectName().'.',
            'follow_up_id' => $this->followUp->id,
            'scheduled_at' => $this->followUp->scheduled_at?->toIso8601String(),
        ];
    }
}
