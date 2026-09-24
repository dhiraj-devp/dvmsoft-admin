<?php

namespace App\Services\Ai;

use App\Enums\ProjectPriority;
use App\Models\Requirement;

class RequirementAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(Requirement $requirement): array
    {
        $requirement->loadMissing('project');

        return $this->generate('requirement', $this->context($requirement), $requirement);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(Requirement $requirement): array
    {
        return [
            'requirement' => [
                'title' => $requirement->title,
                'description' => $requirement->description,
                'priority' => $requirement->priority->value,
                'status' => $requirement->status->value,
                'client_approval_status' => $requirement->client_approval_status->value,
                'notes' => $requirement->notes,
            ],
            'project' => [
                'number' => $requirement->project?->number,
                'name' => $requirement->project?->name,
                'status' => $requirement->project?->status->value,
            ],
        ];
    }

    /**
     * Persist generated content only after an explicit staff Apply action.
     *
     * @param  array<string, mixed>  $result
     */
    public function apply(Requirement $requirement, array $result): Requirement
    {
        $notes = trim((string) $requirement->notes);
        $additions = [];

        if (! empty($result['acceptance_criteria']) && is_array($result['acceptance_criteria'])) {
            $additions[] = 'Acceptance criteria:'."\n".collect($result['acceptance_criteria'])->map(fn ($item) => '- '.$item)->implode("\n");
        }

        if (! empty($result['missing_information']) && is_array($result['missing_information'])) {
            $additions[] = 'Open questions:'."\n".collect($result['missing_information'])->map(fn ($item) => '- '.$item)->implode("\n");
        }

        if (! empty($result['risks']) && is_array($result['risks'])) {
            $additions[] = 'Risks / edge cases:'."\n".collect($result['risks'])->map(fn ($item) => '- '.$item)->implode("\n");
        }

        $payload = [
            'description' => (string) ($result['clarified_requirement'] ?? $requirement->description),
        ];

        if ($additions !== []) {
            $payload['notes'] = trim($notes."\n\n".implode("\n\n", $additions));
        }

        $suggested = (string) ($result['suggested_priority'] ?? '');
        if (ProjectPriority::tryFrom($suggested)) {
            $payload['priority'] = $suggested;
        }

        $requirement->update($payload);

        $this->auditApplied($requirement, [
            'feature' => 'requirement',
            'fields' => array_keys($payload),
        ]);

        return $requirement->fresh();
    }
}
