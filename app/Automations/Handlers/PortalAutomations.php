<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Models\Automation;

class PortalAutomations implements AutomationHandler
{
    public function handle(Automation $automation): AutomationResult
    {
        return new AutomationResult;
    }
}
