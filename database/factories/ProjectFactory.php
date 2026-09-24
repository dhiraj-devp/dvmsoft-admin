<?php

namespace Database\Factories;

use App\Enums\ClientApprovalMode;
use App\Enums\ProjectHealth;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'PRJ-TEST-'.fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'manager_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date' => now()->toDateString(),
            'expected_end_date' => now()->addMonth()->toDateString(),
            'budget' => 150000,
            'status' => ProjectStatus::Planning,
            'health' => ProjectHealth::Green,
            'priority' => ProjectPriority::Medium,
            'client_approval_mode' => ClientApprovalMode::Flexible,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Active]);
    }
}
