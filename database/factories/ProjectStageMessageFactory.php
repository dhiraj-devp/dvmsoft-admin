<?php

namespace Database\Factories;

use App\Models\ProjectStage;
use App\Models\ProjectStageMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStageMessage>
 */
class ProjectStageMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_id' => ProjectStage::factory(),
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
            'is_internal' => false,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn () => ['is_internal' => true]);
    }
}
