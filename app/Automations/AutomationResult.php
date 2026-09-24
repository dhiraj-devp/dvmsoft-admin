<?php

namespace App\Automations;

class AutomationResult
{
    public function __construct(
        public int $processed = 0,
        public int $notified = 0,
    ) {}

    public function increment(int $processed = 1, int $notified = 0): self
    {
        $this->processed += $processed;
        $this->notified += $notified;

        return $this;
    }
}
