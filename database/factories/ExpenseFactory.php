<?php

namespace Database\Factories;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'EXP-TEST-'.fake()->unique()->numerify('######'),
            'recorded_by_id' => User::factory(),
            'expense_date' => now()->toDateString(),
            'category' => 'software',
            'vendor' => fake()->company(),
            'amount' => 2500,
            'tax_amount' => 450,
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => ExpenseStatus::Approved,
        ];
    }
}
