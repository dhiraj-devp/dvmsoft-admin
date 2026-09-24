<?php

namespace App\Notifications\Concerns;

use App\Models\NotificationPreference;

trait ResolvesNotificationChannels
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $preference = NotificationPreference::for($notifiable);
        $channels = [];

        if (settings('notifications.in_app_enabled', true) && $preference->in_app_enabled) {
            $channels[] = 'database';
        }

        if (settings('notifications.email_enabled', false) && $preference->email_enabled) {
            $channels[] = 'mail';
        }

        $allowlist = $this->channelAllowlist ?? null;

        if (is_array($allowlist)) {
            $channels = array_values(array_intersect($channels, $allowlist));
        }

        return $channels;
    }
}
