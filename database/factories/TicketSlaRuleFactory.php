<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\TicketSlaRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSlaRule>
 */
class TicketSlaRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'priority' => TicketPriority::Normal,
            'hours' => 24,
            'warning_hours' => 4,
        ];
    }
}
