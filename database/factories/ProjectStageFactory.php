<?php

namespace Database\Factories;

use App\Enums\StageApprovalRequirement;
use App\Enums\StageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStage>
 */
class ProjectStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Discovery', 'Design', 'Development', 'QA', 'Deployment']),
            'description' => fake()->sentence(),
            'sequence' => 1,
            'status' => StageStatus::NotStarted,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'completion_percentage' => 0,
            'client_review_enabled' => true,
            'approval_requirement' => StageApprovalRequirement::Inherit,
            'requires_evidence' => false,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => StageStatus::InProgress]);
    }

    public function readyForReview(): static
    {
        return $this->state(fn () => ['status' => StageStatus::ReadyForReview]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => StageStatus::Completed,
            'completed_at' => now(),
            'completion_percentage' => 100,
        ]);
    }
}
