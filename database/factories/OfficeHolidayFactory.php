<?php

namespace Database\Factories;

use App\Models\OfficeHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeHoliday>
 */
class OfficeHolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'holiday_date' => fake()->unique()->date(),
            'title' => 'Holiday',
        ];
    }
}
