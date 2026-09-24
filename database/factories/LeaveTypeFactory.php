<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        $code = 'leave-'.fake()->unique()->numerify('####');

        return [
            'name' => 'Annual leave',
            'code' => $code,
            'days_per_year' => 18,
            'is_paid' => true,
            'is_active' => true,
        ];
    }
}
