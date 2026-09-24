<?php

namespace Database\Factories;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assigned_user_id' => User::factory(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('98########'),
            'source' => 'website',
            'requirement' => fake()->sentence(),
            'estimated_value' => fake()->numberBetween(25000, 500000),
            'status' => LeadStatus::New,
            'priority' => LeadPriority::Medium,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function won(): static
    {
        return $this->state(fn () => ['status' => LeadStatus::Won]);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => LeadStatus::Lost]);
    }
}
