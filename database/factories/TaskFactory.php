<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'priority' => ProjectPriority::Medium,
            'status' => TaskStatus::Todo,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'estimated_hours' => 8,
        ];
    }
}
