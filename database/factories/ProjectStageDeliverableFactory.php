<?php

namespace Database\Factories;

use App\Enums\StageDeliverableStatus;
use App\Models\ProjectStage;
use App\Models\ProjectStageDeliverable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStageDeliverable>
 */
class ProjectStageDeliverableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_id' => ProjectStage::factory(),
            'name' => fake()->randomElement(['Desktop design', 'Mobile design', 'Design system', 'Figma link']),
            'description' => fake()->sentence(),
            'is_required' => true,
            'status' => StageDeliverableStatus::Pending,
            'sequence' => 1,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => StageDeliverableStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }
}
