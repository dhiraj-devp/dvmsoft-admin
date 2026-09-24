<?php

namespace Tests\Feature\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_support_routes(): void
    {
        $this->get(route('support.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->get(route('ticket-categories.index'))->assertRedirect(route('login'));
        $this->get(route('ticket-sla.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_support_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('support.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('tickets.index'))->assertForbidden();
        $this->actingAs($user)->get(route('tickets.create'))->assertForbidden();
        $this->actingAs($user)->get(route('ticket-categories.index'))->assertForbidden();
        $this->actingAs($user)->get(route('ticket-sla.index'))->assertForbidden();
    }

    public function test_super_admin_can_open_support_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)->get(route('support.dashboard'))->assertOk()->assertSee('Open tickets');
        $this->actingAs($admin)->get(route('tickets.index'))->assertOk();
        $this->actingAs($admin)->get(route('tickets.create'))->assertOk();
        $this->actingAs($admin)->get(route('tickets.show', $ticket))->assertOk();
        $this->actingAs($admin)->get(route('ticket-categories.index'))->assertOk();
        $this->actingAs($admin)->get(route('ticket-sla.index'))->assertOk();
    }

    public function test_granular_ticket_permissions_and_support_executive_seed(): void
    {
        $viewer = $this->userWithPermissions(['tickets.view', 'support.dashboard.view']);

        $this->actingAs($viewer)->get(route('support.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('tickets.index'))->assertOk();
        $this->actingAs($viewer)->get(route('tickets.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('ticket-categories.index'))->assertForbidden();

        $this->seed();
        $role = Role::query()->where('slug', 'support-executive')->first();
        $this->assertTrue($role->permissions()->where('name', 'tickets.view')->exists());
        $this->assertTrue($role->permissions()->where('name', 'tickets.internal_notes')->exists());
        $this->assertTrue($role->permissions()->where('name', 'support.dashboard.view')->exists());
        $this->assertFalse($role->permissions()->where('name', 'invoices.create')->exists());
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Support Viewer',
            'slug' => 'support-viewer-'.uniqid(),
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
