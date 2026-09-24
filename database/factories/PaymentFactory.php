<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory()->sent(),
            'client_id' => fn (array $attributes) => Invoice::query()->find($attributes['invoice_id'])?->client_id ?? Client::factory(),
            'recorded_by_id' => User::factory(),
            'amount' => 5000,
            'paid_on' => now()->toDateString(),
            'method' => PaymentMethod::Upi,
            'reference' => 'TXN'.fake()->numerify('######'),
        ];
    }
}
