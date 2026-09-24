<?php

namespace Database\Factories;

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->sentence(),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'status' => MilestoneStatus::Pending,
            'completion_percentage' => 0,
        ];
    }
}
