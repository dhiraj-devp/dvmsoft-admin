<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('dvmsoft.departments') as $name) {
            Department::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $name.' department',
                    'is_active' => true,
                ]
            );
        }
    }
}
