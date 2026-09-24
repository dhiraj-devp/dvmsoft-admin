<?php

namespace App\Services\Ai;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Quotation;

class QuotationAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(?Quotation $quotation = null, ?Lead $lead = null, ?Client $client = null): array
    {
        return $this->generate('quotation', $this->context($quotation, $lead, $client), $quotation);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(?Quotation $quotation = null, ?Lead $lead = null, ?Client $client = null): array
    {
        $quotation?->loadMissing(['client', 'lead', 'items']);
        $lead ??= $quotation?->lead;
        $client ??= $quotation?->client;

        return [
            'client' => $client ? [
                'name' => $client->name,
                'type' => $client->type?->value,
            ] : null,
            'lead' => $lead ? [
                'name' => $lead->name,
                'company' => $lead->company,
                'requirement' => $lead->requirement,
                'status' => $lead->status->value,
            ] : null,
            'quotation' => $quotation ? [
                'number' => $quotation->number,
                'title' => $quotation->title,
                'status' => $quotation->status->value,
                'payment_terms' => $quotation->payment_terms,
                'notes' => $quotation->notes,
                'item_descriptions' => $quotation->items->pluck('description')->all(),
                'item_count' => $quotation->items->count(),
            ] : null,
            'rules' => [
                'never_invent_prices' => true,
                'never_change_totals' => true,
                'descriptions_and_wording_only' => true,
            ],
        ];
    }
}
