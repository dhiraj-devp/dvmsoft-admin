<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'followable_type' => Lead::class,
            'followable_id' => Lead::factory(),
            'assigned_user_id' => User::factory(),
            'scheduled_at' => now()->addDay(),
            'type' => FollowUpType::Call,
            'notes' => fake()->sentence(),
            'status' => FollowUpStatus::Pending,
            'reminder_at' => now()->addHours(20),
        ];
    }
}
