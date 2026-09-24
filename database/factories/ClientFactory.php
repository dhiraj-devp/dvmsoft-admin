<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_manager_id' => User::factory(),
            'type' => ClientType::Company,
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('98########'),
            'website' => 'https://example.com',
            'address' => fake()->address(),
            'gst_number' => strtoupper(fake()->bothify('??##########?#')),
            'status' => ClientStatus::Active,
        ];
    }
}
