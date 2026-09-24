<?php

namespace App\Jobs;

use App\Automations\AutomationEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAutomationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $key, public bool $force = true) {}

    public function handle(AutomationEngine $engine): void
    {
        $engine->run($this->key, $this->force);
    }
}
