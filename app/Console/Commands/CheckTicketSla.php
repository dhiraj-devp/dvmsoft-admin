<?php

namespace App\Console\Commands;

use App\Automations\AutomationEngine;
use Illuminate\Console\Command;

class CheckTicketSla extends Command
{
    protected $signature = 'support:check-sla';

    protected $description = 'Notify assignees when ticket SLAs are approaching or breached';

    public function handle(AutomationEngine $engine): int
    {
        $breached = $engine->run('support.sla_breached', true);
        $approaching = $engine->run('support.sla_approaching', true);

        $this->info($approaching->notified_count.' approaching and '.$breached->notified_count.' breached SLA notification(s) sent.');

        return self::SUCCESS;
    }
}
