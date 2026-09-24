<?php

namespace App\Livewire\Ai;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Quotation;
use App\Services\Ai\QuotationAssistant;
use Illuminate\Contracts\View\View;

class QuotationPanel extends AiPanel
{
    public ?string $quotationId = null;

    public ?string $leadId = null;

    public ?string $clientId = null;

    public bool $canApplyToForm = false;

    public function mount(?string $quotationId = null, ?string $leadId = null, ?string $clientId = null, bool $canApplyToForm = false): void
    {
        $this->quotationId = $quotationId;
        $this->leadId = $leadId;
        $this->clientId = $clientId;
        $this->canApplyToForm = $canApplyToForm;
        $this->authorize($this->permission());

        if ($quotationId) {
            $this->authorize('view', Quotation::query()->findOrFail($quotationId));
        }
    }

    public function generateDraft(QuotationAssistant $assistant): void
    {
        $quotation = filled($this->quotationId) ? Quotation::query()->findOrFail($this->quotationId) : null;
        $lead = filled($this->leadId) ? Lead::query()->find($this->leadId) : $quotation?->lead;
        $client = filled($this->clientId) ? Client::query()->find($this->clientId) : $quotation?->client;
        $user = request()->user();

        if ($quotation) {
            $this->authorize('view', $quotation);
        }

        if ($lead && $user->cannot('view', $lead)) {
            $lead = null;
        }

        if ($client && $user->cannot('view', $client)) {
            $client = null;
        }

        $this->generateWith(fn () => $assistant->analyze($quotation, $lead, $client), $quotation);
    }

    public function applyToForm(): void
    {
        $this->authorize($this->permission());

        if (! $this->canApplyToForm || ! $this->result) {
            return;
        }

        $this->dispatch(
            'ai-apply-quotation',
            title: (string) ($this->result['title'] ?? ''),
            paymentTermsWording: (string) ($this->result['payment_terms_wording'] ?? ''),
            descriptions: $this->list('line_item_descriptions'),
        );

        $this->applied = true;
        $this->dispatch('notify', type: 'success', message: 'Suggestions filled into the form. Prices and totals were not changed. Save when you are ready.');
    }

    public function render(): View
    {
        return view('livewire.ai.quotation-panel');
    }

    protected function feature(): string
    {
        return 'quotation';
    }

    protected function permission(): string
    {
        return 'ai.quotations.use';
    }
}
