<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'QT-TEST-'.fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'created_by_id' => User::factory(),
            'title' => fake()->sentence(3),
            'status' => QuotationStatus::Draft,
            'subtotal' => 10000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 18,
            'tax_amount' => 1800,
            'total' => 11800,
            'payment_terms' => 'net_15',
            'valid_until' => now()->addDays(15),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => QuotationStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => QuotationStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => QuotationStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn () => [
            'status' => QuotationStatus::Converted,
            'converted_at' => now(),
        ]);
    }

    public function withItem(): static
    {
        return $this->afterCreating(function (Quotation $quotation): void {
            $quotation->items()->create([
                'description' => 'Software development',
                'quantity' => 1,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'tax_percent' => 18,
                'line_total' => 11800,
                'sort_order' => 0,
            ]);
        });
    }
}
