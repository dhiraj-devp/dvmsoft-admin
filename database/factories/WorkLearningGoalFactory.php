<?php

namespace Database\Factories;

use App\Enums\WorkLearningStatus;
use App\Models\User;
use App\Models\WorkLearningGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkLearningGoal>
 */
class WorkLearningGoalFactory extends Factory
{
    public function definition(): array
    {
        $owner = User::factory();

        return [
            'assigned_user_id' => $owner,
            'created_by' => $owner,
            'title' => 'Learn '.fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'target_date' => now()->addDays(10)->toDateString(),
            'status' => WorkLearningStatus::Learning,
        ];
    }
}
