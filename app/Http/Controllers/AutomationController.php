<?php

namespace App\Http\Controllers;

use App\Automations\AutomationEngine;
use App\Enums\AutomationRunStatus;
use App\Models\Automation;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function index(AutomationEngine $engine): View
    {
        $this->authorize('viewAny', Automation::class);
        $engine->syncCatalog();

        $automations = Automation::query();

        return view('automations.index', [
            'metrics' => [
                [
                    'label' => 'Active',
                    'value' => (clone $automations)->where('enabled', true)->count(),
                    'hint' => 'Enabled in the catalog',
                    'icon' => 'bolt',
                ],
                [
                    'label' => 'Disabled',
                    'value' => (clone $automations)->where('enabled', false)->count(),
                    'hint' => 'Paused automations',
                    'icon' => 'clock',
                ],
                [
                    'label' => 'Failed',
                    'value' => (clone $automations)->where('last_status', AutomationRunStatus::Failed->value)->count(),
                    'hint' => 'Last run failed',
                    'icon' => 'alert',
                ],
                [
                    'label' => 'Event-driven',
                    'value' => (clone $automations)->where('trigger_type', 'event')->count(),
                    'hint' => 'Workflow hooks',
                    'icon' => 'bell',
                ],
            ],
        ]);
    }

    public function show(Automation $automation): View
    {
        $this->authorize('view', $automation);

        return view('automations.show', compact('automation'));
    }
}
