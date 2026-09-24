<?php

namespace Database\Factories;

use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeRequest>
 */
class ChangeRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'CR-TEST-'.fake()->unique()->numerify('######'),
            'project_id' => Project::factory(),
            'requested_by_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'impact_on_cost' => 25000,
            'impact_on_timeline_days' => 7,
            'status' => ChangeRequestStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ChangeRequestStatus::Pending]);
    }
}
