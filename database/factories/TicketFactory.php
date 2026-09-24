<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'TKT-TEST-'.fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'created_by_id' => User::factory(),
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority' => TicketPriority::Normal,
            'status' => TicketStatus::Open,
            'sla_due_at' => now()->addHours(24),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TicketStatus::InProgress]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Resolved,
            'resolution' => 'Fixed',
            'resolved_at' => now(),
        ]);
    }
}
