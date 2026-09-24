<?php

namespace App\Services\Ai;

use App\Models\Lead;

class LeadAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(Lead $lead): array
    {
        $lead->loadMissing(['assignedUser', 'convertedClient', 'activities.user', 'followUps']);

        return $this->generate('lead', $this->context($lead), $lead);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(Lead $lead): array
    {
        return [
            'lead' => [
                'name' => $lead->name,
                'company' => $lead->company,
                'source' => $lead->source,
                'status' => $lead->status->value,
                'priority' => $lead->priority->value,
                'requirement' => $lead->requirement,
                'estimated_value' => $lead->estimated_value,
                'notes' => $lead->notes,
                'lost_reason' => $lead->lost_reason,
                'next_follow_up_at' => optional($lead->next_follow_up_at)?->toDateTimeString(),
                'owner' => $lead->assignedUser?->name,
                'converted' => $lead->isConverted(),
            ],
            'activities' => $lead->activities->take(12)->map(fn ($activity) => [
                'type' => $activity->type,
                'title' => $activity->title,
                'body' => $activity->body,
                'at' => optional($activity->created_at)?->toDateTimeString(),
            ])->all(),
            'follow_ups' => $lead->followUps->take(8)->map(fn ($followUp) => [
                'type' => $followUp->type->value,
                'status' => $followUp->status->value,
                'scheduled_at' => optional($followUp->scheduled_at)?->toDateTimeString(),
                'notes' => $followUp->notes,
            ])->all(),
        ];
    }
}
