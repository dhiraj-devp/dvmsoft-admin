<?php

namespace Database\Factories;

use App\Enums\WorkGoalStatus;
use App\Enums\WorkPriority;
use App\Models\User;
use App\Models\WorkGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkGoal>
 */
class WorkGoalFactory extends Factory
{
    public function definition(): array
    {
        $owner = User::factory();

        return [
            'assigned_user_id' => $owner,
            'created_by' => $owner,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'expected_outcome' => fake()->optional()->sentence(),
            'start_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'priority' => WorkPriority::Medium,
            'status' => WorkGoalStatus::InProgress,
            'progress' => 20,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(2)->toDateString(),
            'status' => WorkGoalStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => WorkGoalStatus::Completed,
            'progress' => 100,
        ]);
    }
}
