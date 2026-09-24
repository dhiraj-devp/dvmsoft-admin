<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_sales_routes(): void
    {
        $this->get(route('sales.dashboard'))->assertRedirect(route('login'));
        $this->get(route('leads.index'))->assertRedirect(route('login'));
        $this->get(route('clients.index'))->assertRedirect(route('login'));
        $this->get(route('contacts.index'))->assertRedirect(route('login'));
        $this->get(route('follow-ups.index'))->assertRedirect(route('login'));
        $this->get(route('quotations.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_crm_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('leads.index'))->assertForbidden();
        $this->actingAs($user)->get(route('clients.index'))->assertForbidden();
        $this->actingAs($user)->get(route('contacts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('follow-ups.index'))->assertForbidden();
        $this->actingAs($user)->get(route('quotations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('quotations.create'))->assertForbidden();
    }

    public function test_super_admin_can_open_all_sales_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = Lead::factory()->create();
        $client = Client::factory()->create();
        $quotation = Quotation::factory()->withItem()->create();

        $this->actingAs($admin)->get(route('sales.dashboard'))->assertOk()->assertSee('Total leads');
        $this->actingAs($admin)->get(route('leads.index'))->assertOk();
        $this->actingAs($admin)->get(route('leads.show', $lead))->assertOk();
        $this->actingAs($admin)->get(route('clients.index'))->assertOk();
        $this->actingAs($admin)->get(route('clients.show', $client))->assertOk();
        $this->actingAs($admin)->get(route('contacts.index'))->assertOk();
        $this->actingAs($admin)->get(route('follow-ups.index'))->assertOk();
        $this->actingAs($admin)->get(route('quotations.index'))->assertOk();
        $this->actingAs($admin)->get(route('quotations.create'))->assertOk();
        $this->actingAs($admin)->get(route('quotations.show', $quotation))->assertOk();
        $this->actingAs($admin)->get(route('quotations.edit', $quotation))->assertOk();
    }

    public function test_granular_permissions_allow_only_granted_sales_actions(): void
    {
        $viewer = $this->userWithPermissions(['sales.view', 'leads.view']);

        $this->actingAs($viewer)->get(route('sales.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('leads.index'))->assertOk();
        $this->actingAs($viewer)->get(route('clients.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('quotations.index'))->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'CRM Viewer',
            'slug' => 'crm-viewer-'.uniqid(),
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
