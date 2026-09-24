<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('hr.leave_types', []) as $type) {
            LeaveType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'days_per_year' => $type['days_per_year'],
                    'is_paid' => ($type['code'] ?? '') !== 'unpaid',
                    'is_active' => true,
                ]
            );
        }
    }
}
