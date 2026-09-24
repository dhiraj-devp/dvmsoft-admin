<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_finance_routes(): void
    {
        $this->get(route('finance.dashboard'))->assertRedirect(route('login'));
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
        $this->get(route('payments.index'))->assertRedirect(route('login'));
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
        $this->get(route('finance.outstanding'))->assertRedirect(route('login'));
        $this->get(route('finance.reports'))->assertRedirect(route('login'));
    }

    public function test_users_without_finance_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('finance.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('payments.index'))->assertForbidden();
        $this->actingAs($user)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($user)->get(route('finance.reports'))->assertForbidden();
    }

    public function test_super_admin_can_open_finance_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $invoice = Invoice::factory()->withItem()->create();

        $this->actingAs($admin)->get(route('finance.dashboard'))->assertOk()->assertSee('Total revenue');
        $this->actingAs($admin)->get(route('invoices.index'))->assertOk();
        $this->actingAs($admin)->get(route('invoices.create'))->assertOk();
        $this->actingAs($admin)->get(route('invoices.show', $invoice))->assertOk();
        $this->actingAs($admin)->get(route('payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('expenses.index'))->assertOk();
        $this->actingAs($admin)->get(route('finance.outstanding'))->assertOk();
        $this->actingAs($admin)->get(route('finance.reports'))->assertOk();
    }

    public function test_granular_permissions_allow_only_granted_finance_actions(): void
    {
        $viewer = $this->userWithPermissions(['finance.dashboard.view', 'invoices.view']);

        $this->actingAs($viewer)->get(route('finance.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('invoices.index'))->assertOk();
        $this->actingAs($viewer)->get(route('invoices.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('payments.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('finance.reports'))->assertForbidden();
    }

    public function test_users_without_send_permission_cannot_send_invoices(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->withItem()->create();

        $this->actingAs($user)
            ->post(route('invoices.send', $invoice))
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Finance Viewer',
            'slug' => 'finance-viewer-'.uniqid(),
        ]);

        $ids = collect($permissions)->map(function (string $name) {
            return Permission::query()->firstOrCreate(
                ['name' => $name],
                [
                    'group' => explode('.', $name)[0],
                    'description' => $name,
                ]
            )->id;
        });

        $role->permissions()->sync($ids);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
