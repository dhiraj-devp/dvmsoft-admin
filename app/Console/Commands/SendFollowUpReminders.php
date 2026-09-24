<?php

namespace App\Console\Commands;

use App\Automations\AutomationEngine;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'crm:send-follow-up-reminders';

    protected $description = 'Notify assignees about due CRM follow-up reminders';

    public function handle(AutomationEngine $engine): int
    {
        $run = $engine->run('crm.follow_up_due', true);

        $this->info($run->notified_count.' follow-up reminder(s) sent.');

        return self::SUCCESS;
    }
}
