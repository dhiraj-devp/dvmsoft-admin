<?php

namespace Database\Factories;

use App\Enums\AssetCondition;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAsset>
 */
class EmployeeAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'assigned_by_id' => User::factory(),
            'name' => 'Laptop',
            'serial_number' => 'SN'.fake()->unique()->numerify('######'),
            'assigned_date' => now()->toDateString(),
            'condition' => AssetCondition::Good,
        ];
    }
}
