<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'INV-TEST-'.fake()->unique()->numerify('######'),
            'client_id' => Client::factory(),
            'created_by_id' => User::factory(),
            'title' => fake()->sentence(3),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => InvoiceStatus::Draft,
            'subtotal' => 10000,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_percent' => 18,
            'tax_amount' => 1800,
            'total' => 11800,
            'amount_paid' => 0,
            'balance' => 11800,
            'payment_terms' => 'net_15',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function withItem(): static
    {
        return $this->afterCreating(function (Invoice $invoice): void {
            $invoice->items()->create([
                'description' => 'Professional services',
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
