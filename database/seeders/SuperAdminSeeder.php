<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@dvmsoft.local');
        $password = env('ADMIN_PASSWORD', 'password');

        $department = Department::query()->where('slug', 'management')->first();
        $role = Role::query()->where('slug', 'super-admin')->first();

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Super Admin'),
                'password' => $password,
                'department_id' => $department?->id,
                'job_title' => 'Super Administrator',
                'employee_code' => 'DVM-0001',
                'is_active' => true,
                'is_super_admin' => true,
                'theme' => 'system',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
