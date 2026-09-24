<?php

namespace Tests\Feature\Reports;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_report_routes(): void
    {
        $this->get(route('reports.overview'))->assertRedirect(route('login'));
        $this->get(route('reports.sales'))->assertRedirect(route('login'));
        $this->get(route('reports.projects'))->assertRedirect(route('login'));
        $this->get(route('reports.finance'))->assertRedirect(route('login'));
        $this->get(route('reports.hr'))->assertRedirect(route('login'));
        $this->get(route('reports.support'))->assertRedirect(route('login'));
        $this->get(route('reports.export', 'sales'))->assertRedirect(route('login'));
    }

    public function test_users_without_report_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.overview'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.sales'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.projects'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.finance'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.hr'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.support'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.export', 'overview'))->assertForbidden();
    }

    public function test_super_admin_can_open_all_report_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('reports.overview'))->assertOk()->assertSee('Revenue');
        $this->actingAs($admin)->get(route('reports.sales'))->assertOk()->assertSee('Leads by status');
        $this->actingAs($admin)->get(route('reports.projects'))->assertOk()->assertSee('Projects by status');
        $this->actingAs($admin)->get(route('reports.finance'))->assertOk()->assertSee('Monthly revenue trend');
        $this->actingAs($admin)->get(route('reports.hr'))->assertOk()->assertSee('Total employees');
        $this->actingAs($admin)->get(route('reports.support'))->assertOk()->assertSee('Tickets by status');
    }

    public function test_granular_report_permissions_hide_other_modules(): void
    {
        $salesOnly = $this->userWithPermissions(['reports.sales.view']);

        $this->actingAs($salesOnly)->get(route('reports.sales'))->assertOk();
        $this->actingAs($salesOnly)->get(route('reports.overview'))->assertForbidden();
        $this->actingAs($salesOnly)->get(route('reports.finance'))->assertForbidden();
        $this->actingAs($salesOnly)->get(route('reports.hr'))->assertForbidden();
        $this->actingAs($salesOnly)->get(route('reports.export', 'sales'))->assertForbidden();

        $exporter = $this->userWithPermissions(['reports.export']);
        $this->actingAs($exporter)->get(route('reports.export', 'sales'))->assertForbidden();

        $this->seed();
        $accountant = Role::query()->where('slug', 'accountant')->first();
        $this->assertTrue($accountant->permissions()->where('name', 'reports.finance.view')->exists());
        $this->assertFalse($accountant->permissions()->where('name', 'reports.hr.view')->exists());
        $this->assertFalse($accountant->permissions()->where('name', 'payroll.view')->exists());
    }

    public function test_overview_only_shows_authorized_kpis(): void
    {
        $viewer = $this->userWithPermissions(['reports.view', 'reports.sales.view']);

        $this->actingAs($viewer)
            ->get(route('reports.overview'))
            ->assertOk()
            ->assertSee('Pipeline value')
            ->assertDontSee('Total employees');
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Report Limited',
            'slug' => 'report-limited-'.uniqid(),
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
