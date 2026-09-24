<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\RequirementApprovalStatus;
use App\Enums\RequirementStatus;
use App\Models\Project;
use App\Models\Requirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Requirement>
 */
class RequirementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'priority' => ProjectPriority::Medium,
            'status' => RequirementStatus::Draft,
            'client_approval_status' => RequirementApprovalStatus::Pending,
        ];
    }
}
