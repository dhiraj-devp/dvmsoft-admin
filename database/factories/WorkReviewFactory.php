<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkReview>
 */
class WorkReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reviewer_id' => User::factory(),
            'quality_score' => 4,
            'feedback' => fake()->sentence(),
            'action_items' => fake()->optional()->sentence(),
            'reviewed_on' => now()->toDateString(),
        ];
    }
}
