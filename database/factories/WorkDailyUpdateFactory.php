<?php

namespace Database\Factories;

use App\Enums\WorkDailyUpdateStatus;
use App\Models\User;
use App\Models\WorkDailyUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkDailyUpdate>
 */
class WorkDailyUpdateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_date' => now()->toDateString(),
            'accomplished' => fake()->paragraph(),
            'pending' => fake()->optional()->sentence(),
            'blocked' => fake()->optional()->sentence(),
            'learned' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'submitted_at' => now(),
            'status' => WorkDailyUpdateStatus::InReview,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => WorkDailyUpdateStatus::Done,
            'reviewed_at' => now(),
        ]);
    }
}
