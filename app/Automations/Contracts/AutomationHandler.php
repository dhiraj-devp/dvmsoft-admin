<?php

namespace App\Automations\Contracts;

use App\Automations\AutomationResult;
use App\Models\Automation;

interface AutomationHandler
{
    public function handle(Automation $automation): AutomationResult;
}
