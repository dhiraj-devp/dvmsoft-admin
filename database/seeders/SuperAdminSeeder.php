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

    public const SUPER_ADMIN_NAME = 'Super Admin';

    public const SUPER_ADMIN_EMAIL = 'admin@dvmsoft.local';

    public const SUPER_ADMIN_PASSWORD = 'password';

    public const ADMIN_NAME = 'Admin';

    public const ADMIN_EMAIL = 'office@dvmsoft.local';

    public const ADMIN_PASSWORD = 'password';

    public function run(): void
    {
        $department = Department::query()->where('slug', 'management')->first();

        $this->ensureUser(
            self::SUPER_ADMIN_EMAIL,
            [
                'name' => self::SUPER_ADMIN_NAME,
                'password' => self::SUPER_ADMIN_PASSWORD,
                'department_id' => $department?->id,
                'job_title' => 'Super Administrator',
                'employee_code' => 'DVM-0001',
                'is_active' => true,
                'is_super_admin' => true,
                'theme' => 'system',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ],
            Role::query()->where('slug', 'super-admin')->first(),
        );

        $this->ensureUser(
            self::ADMIN_EMAIL,
            [
                'name' => self::ADMIN_NAME,
                'password' => self::ADMIN_PASSWORD,
                'department_id' => $department?->id,
                'job_title' => 'Administrator',
                'employee_code' => 'DVM-0002',
                'is_active' => true,
                'is_super_admin' => false,
                'theme' => 'system',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ],
            Role::query()->where('slug', 'admin')->first(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function ensureUser(string $email, array $attributes, ?Role $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            $attributes,
        );

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }
}
