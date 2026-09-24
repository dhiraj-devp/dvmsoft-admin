<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Models\Automation;
use App\Services\DocumentNotificationService;

class DocumentAutomations implements AutomationHandler
{
    public function __construct(protected DocumentNotificationService $documents) {}

    public function handle(Automation $automation): AutomationResult
    {
        $result = match ($automation->key) {
            'documents.expiry_approaching' => $this->documents->notifyExpiring(),
            'documents.expired' => $this->documents->notifyExpired(),
            default => ['processed' => 0, 'notified' => 0],
        };

        return new AutomationResult($result['processed'], $result['notified']);
    }
}
