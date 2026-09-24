<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SettingSeeder::class,
            SuperAdminSeeder::class,
            LeaveTypeSeeder::class,
            SupportSeeder::class,
            DocumentTypeSeeder::class,
            AutomationSeeder::class,
        ]);
    }
}
