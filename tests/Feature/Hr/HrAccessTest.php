<?php

namespace Tests\Feature\Hr;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_hr_routes(): void
    {
        $this->get(route('hr.dashboard'))->assertRedirect(route('login'));
        $this->get(route('employees.index'))->assertRedirect(route('login'));
        $this->get(route('leave.index'))->assertRedirect(route('login'));
        $this->get(route('assets.index'))->assertRedirect(route('login'));
        $this->get(route('onboarding.index'))->assertRedirect(route('login'));
        $this->get(route('offboarding.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_hr_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('hr.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('employees.index'))->assertForbidden();
        $this->actingAs($user)->get(route('leave.index'))->assertForbidden();
        $this->actingAs($user)->get(route('assets.index'))->assertForbidden();
        $this->actingAs($user)->get(route('onboarding.index'))->assertForbidden();
        $this->actingAs($user)->get(route('offboarding.index'))->assertForbidden();
    }

    public function test_super_admin_can_open_hr_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($admin)->get(route('hr.dashboard'))->assertOk()->assertSee('Total employees');
        $this->actingAs($admin)->get(route('employees.index'))->assertOk();
        $this->actingAs($admin)->get(route('employees.create'))->assertOk();
        $this->actingAs($admin)->get(route('employees.show', $employee))->assertOk();
        $this->actingAs($admin)->get(route('leave.index'))->assertOk();
        $this->actingAs($admin)->get(route('assets.index'))->assertOk();
        $this->actingAs($admin)->get(route('onboarding.index'))->assertOk();
        $this->actingAs($admin)->get(route('offboarding.index'))->assertOk();
    }

    public function test_granular_hr_permissions_and_payroll_is_not_granted_to_plain_employees(): void
    {
        $viewer = $this->userWithPermissions(['hr.dashboard.view', 'employees.view']);

        $this->actingAs($viewer)->get(route('hr.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('employees.index'))->assertOk();
        $this->actingAs($viewer)->get(route('employees.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('leave.index'))->assertForbidden();

        $this->seed();
        $employeeRole = Role::query()->where('slug', 'employee')->first();
        $hrRole = Role::query()->where('slug', 'hr-manager')->first();

        $this->assertFalse($employeeRole->permissions()->where('name', 'like', 'payroll.%')->exists());
        $this->assertFalse($employeeRole->permissions()->where('name', 'employees.view')->exists());
        $this->assertTrue($hrRole->permissions()->where('name', 'hr.dashboard.view')->exists());
        $this->assertTrue($hrRole->permissions()->where('name', 'employees.view')->exists());
        $this->assertFalse($hrRole->permissions()->where('name', 'like', 'payroll.%')->exists());
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'HR Viewer',
            'slug' => 'hr-viewer-'.uniqid(),
        ]);

        $ids = collect($permissions)->map(function (string $name) {
            return Permission::query()->firstOrCreate(
                ['name' => $name],
                ['group' => explode('.', $name)[0], 'description' => $name]
            )->id;
        });

        $role->permissions()->sync($ids);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
