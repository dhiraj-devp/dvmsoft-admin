<?php

namespace Tests\Feature\Automations;

use App\Automations\AutomationEngine;
use App\Livewire\Automations\Index;
use App\Models\AuditLog;
use App\Models\Automation;
use App\Models\ClientUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AutomationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_automations(): void
    {
        $this->get(route('automations.index'))->assertRedirect(route('login'));
    }

    public function test_staff_without_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('automations.index'))->assertForbidden();
    }

    public function test_client_portal_users_cannot_open_internal_automations(): void
    {
        $user = ClientUser::factory()->create();

        $this->actingAs($user, 'client')
            ->get(route('automations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_view_permission_can_open_list_but_not_toggle(): void
    {
        $user = $this->userWithPermissions(['automations.view']);
        app(AutomationEngine::class)->syncCatalog();
        $automation = Automation::query()->where('key', 'crm.follow_up_due')->first();

        $this->actingAs($user)->get(route('automations.index'))->assertOk()->assertSee('Automations');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('toggle', $automation->id)
            ->assertForbidden();
    }

    public function test_manage_permission_can_enable_and_disable(): void
    {
        $user = $this->userWithPermissions(['automations.view', 'automations.manage']);
        app(AutomationEngine::class)->syncCatalog();
        $automation = Automation::query()->where('key', 'crm.follow_up_due')->first();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('toggle', $automation->id)
            ->assertHasNoErrors();

        $this->assertFalse($automation->fresh()->enabled);
        $this->assertTrue(
            AuditLog::query()->where('module', 'automations')->where('action', 'updated')->exists()
        );

        Livewire::test(Index::class)
            ->call('toggle', $automation->id);

        $this->assertTrue($automation->fresh()->enabled);
    }

    public function test_history_permission_is_required_to_see_runs(): void
    {
        $viewer = $this->userWithPermissions(['automations.view']);
        $historian = $this->userWithPermissions(['automations.view', 'automations.history.view']);
        app(AutomationEngine::class)->syncCatalog();
        $automation = Automation::query()->where('key', 'crm.follow_up_due')->first();
        $automation->runs()->create([
            'status' => 'success',
            'processed_count' => 1,
            'notified_count' => 1,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('automations.show', $automation))
            ->assertOk()
            ->assertDontSee('Execution history');

        $this->actingAs($historian)
            ->get(route('automations.show', $automation))
            ->assertOk()
            ->assertSee('Execution history');
    }

    public function test_seeded_roles_do_not_give_employees_automation_manage(): void
    {
        $this->seed();

        $this->assertFalse(
            Role::query()->where('slug', 'employee')->first()
                ->permissions()
                ->where('name', 'automations.manage')
                ->exists()
        );
        $this->assertTrue(
            Role::query()->where('slug', 'admin')->first()
                ->permissions()
                ->where('name', 'automations.manage')
                ->exists()
        );
    }

    public function test_settings_automations_section_is_available(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('settings.section', 'automations'))
            ->assertOk()
            ->assertSee('Enable automations');
    }
}
