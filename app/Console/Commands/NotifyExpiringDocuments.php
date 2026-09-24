<?php

namespace App\Console\Commands;

use App\Automations\AutomationEngine;
use Illuminate\Console\Command;

class NotifyExpiringDocuments extends Command
{
    protected $signature = 'documents:notify-expiring';

    protected $description = 'Notify owners when company documents are approaching expiry';

    public function handle(AutomationEngine $engine): int
    {
        $run = $engine->run('documents.expiry_approaching', true);

        $this->info($run->notified_count.' expiring document notification(s) sent.');

        return self::SUCCESS;
    }
}
