<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_permission_cannot_view_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_super_admin_can_access_administration_pages(): void
    {
        $this->seed();

        $admin = User::query()->where('is_super_admin', true)->first();

        $this->actingAs($admin)
            ->get('/users')->assertOk();
        $this->actingAs($admin)
            ->get('/roles')->assertOk();
        $this->actingAs($admin)
            ->get('/permissions')->assertOk();
        $this->actingAs($admin)
            ->get('/audit-logs')->assertOk();
        $this->actingAs($admin)
            ->get('/settings')->assertOk();
        $this->actingAs($admin)
            ->get('/settings/company')->assertOk();
        $this->actingAs($admin)
            ->get('/settings/branding')->assertOk();
        $this->actingAs($admin)
            ->get('/settings/localization')->assertOk();
        $this->actingAs($admin)
            ->get('/settings/security')->assertOk();
        $this->actingAs($admin)
            ->get('/settings/ai')->assertOk();
    }

    public function test_permission_grants_access_without_checking_role_names(): void
    {
        $permission = Permission::query()->create([
            'name' => 'users.view',
            'group' => 'users',
            'description' => 'View users',
        ]);

        $role = Role::query()->create([
            'name' => 'Custom Role',
            'slug' => 'custom-role',
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/users')->assertOk();
        $this->actingAs($user)->get('/roles')->assertForbidden();
    }
}
