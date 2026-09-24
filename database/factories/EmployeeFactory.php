<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\ProbationStatus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(fn () => [
                'employee_code' => 'EMP-TEST-'.fake()->unique()->numerify('######'),
                'date_of_joining' => now()->subMonths(2)->toDateString(),
            ]),
            'employment_type' => EmploymentType::FullTime,
            'employment_status' => EmploymentStatus::Active,
            'probation_days' => 90,
            'probation_status' => ProbationStatus::Confirmed,
            'probation_end_date' => now()->subMonth()->toDateString(),
            'confirmation_date' => now()->subMonth()->toDateString(),
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('98########'),
            'photo_disk' => 'hr',
        ];
    }

    public function probation(): static
    {
        return $this->state(fn () => [
            'employment_status' => EmploymentStatus::Probation,
            'probation_status' => ProbationStatus::Ongoing,
            'confirmation_date' => null,
            'probation_end_date' => now()->addDays(30)->toDateString(),
        ]);
    }
}
