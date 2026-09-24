<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use App\Notifications\FollowUpReminderNotification;

class FollowUpReminderService
{
    /**
     * @return array{processed: int, notified: int}
     */
    public function sendDueReminders(): array
    {
        $followUps = FollowUp::query()
            ->with(['assignedUser', 'followable'])
            ->where('status', FollowUpStatus::Pending)
            ->whereNull('reminded_at')
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->get();

        $sent = 0;

        foreach ($followUps as $followUp) {
            if ($followUp->assignedUser) {
                $followUp->assignedUser->notify(new FollowUpReminderNotification($followUp));
                $sent++;
            }

            $followUp->forceFill(['reminded_at' => now()])->saveQuietly();
        }

        return [
            'processed' => $followUps->count(),
            'notified' => $sent,
        ];
    }
}
