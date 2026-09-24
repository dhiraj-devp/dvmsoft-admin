<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Models\Automation;
use App\Services\TicketNotificationService;
use App\Services\TicketSlaService;

class SupportAutomations implements AutomationHandler
{
    public function __construct(
        protected TicketSlaService $sla,
        protected TicketNotificationService $notifications,
    ) {}

    public function handle(Automation $automation): AutomationResult
    {
        $sent = match ($automation->key) {
            'support.sla_approaching' => $this->sla->notifyApproaching($this->notifications),
            'support.sla_breached' => $this->sla->notifyBreached($this->notifications),
            default => 0,
        };

        return new AutomationResult($sent, $sent);
    }
}
