<?php

namespace App\Livewire\Ai;

use App\Models\Lead;
use App\Services\Ai\LeadAssistant;
use Illuminate\Contracts\View\View;

class LeadPanel extends AiPanel
{
    public string $leadId = '';

    public function mount(string $leadId): void
    {
        $this->leadId = $leadId;
        $this->authorize($this->permission());
        $this->authorize('view', Lead::query()->findOrFail($leadId));
    }

    public function generateSummary(LeadAssistant $assistant): void
    {
        $lead = Lead::query()->findOrFail($this->leadId);

        $this->generateWith(fn () => $assistant->analyze($lead), $lead);
    }

    public function render(): View
    {
        return view('livewire.ai.lead-panel');
    }

    protected function feature(): string
    {
        return 'lead';
    }

    protected function permission(): string
    {
        return 'ai.leads.use';
    }
}
