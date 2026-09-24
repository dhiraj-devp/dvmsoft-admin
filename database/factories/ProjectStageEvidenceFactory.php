<?php

namespace Database\Factories;

use App\Enums\StageEvidenceType;
use App\Enums\StageEvidenceVisibility;
use App\Models\ProjectStage;
use App\Models\ProjectStageEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStageEvidence>
 */
class ProjectStageEvidenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_id' => ProjectStage::factory(),
            'uploaded_by_id' => User::factory(),
            'type' => StageEvidenceType::Url,
            'title' => 'Preview link',
            'description' => 'Latest design preview',
            'url' => 'https://www.figma.com/file/example',
            'visibility' => StageEvidenceVisibility::Client,
            'version' => '1',
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['visibility' => StageEvidenceVisibility::Internal]);
    }

    public function text(): static
    {
        return $this->state(fn () => [
            'type' => StageEvidenceType::Text,
            'title' => 'Progress update',
            'url' => null,
        ]);
    }
}
