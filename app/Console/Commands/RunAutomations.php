<?php

namespace App\Console\Commands;

use App\Automations\AutomationEngine;
use App\Jobs\RunAutomationJob;
use Illuminate\Console\Command;

class RunAutomations extends Command
{
    protected $signature = 'automations:run {key?} {--force : Ignore schedule windows} {--sync : Run immediately instead of queueing}';

    protected $description = 'Run due automations through the central workflow engine';

    public function handle(AutomationEngine $engine): int
    {
        $engine->syncCatalog();

        $key = $this->argument('key');
        $force = (bool) $this->option('force');
        $sync = (bool) $this->option('sync');

        $keys = $key ? [$key] : $engine->scheduledKeys();

        foreach ($keys as $automationKey) {
            $automation = $engine->record($automationKey);

            if (! $key && ! $force && ! $engine->isDue($automation)) {
                continue;
            }

            $runForce = $force || (bool) $key;

            if ($sync) {
                $run = $engine->run($automationKey, $runForce);
                $this->line($automationKey.': '.$run->status->value.' (notified '.$run->notified_count.')');

                continue;
            }

            RunAutomationJob::dispatch($automationKey, $runForce);
            $this->line('Queued '.$automationKey);
        }

        return self::SUCCESS;
    }
}
