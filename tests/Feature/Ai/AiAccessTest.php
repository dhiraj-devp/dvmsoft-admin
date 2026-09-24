<?php

namespace Tests\Feature\Ai;

use App\Livewire\Ai\CompanyOverview;
use App\Livewire\Ai\FinanceInsights;
use App\Livewire\Ai\LeadPanel;
use App\Livewire\Ai\ProjectPanel;
use App\Livewire\Ai\QuotationPanel;
use App\Livewire\Ai\RequirementPanel;
use App\Livewire\Ai\TicketPanel;
use App\Models\ClientUser;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_ai_pages(): void
    {
        $this->get(route('ai.overview'))->assertRedirect(route('login'));
        $this->get(route('ai.finance'))->assertRedirect(route('login'));
    }

    public function test_staff_without_ai_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ai.overview'))->assertForbidden();
        $this->actingAs($user)->get(route('ai.finance'))->assertForbidden();
    }

    public function test_client_portal_users_cannot_open_internal_ai_routes(): void
    {
        $user = ClientUser::factory()->create();

        $this->actingAs($user, 'client')
            ->get(route('ai.overview'))
            ->assertRedirect(route('login'));

        $this->actingAs($user, 'client')
            ->get(route('ai.finance'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_open_ai_pages(): void
    {
        $this->enableAi();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('ai.overview'))->assertOk()->assertSee('Generate Insights');
        $this->actingAs($admin)->get(route('ai.finance'))->assertOk()->assertSee('Generate Insights');
        $this->actingAs($admin)->get(route('settings.section', 'ai'))->assertOk()->assertSee('Enable AI assistance');
    }

    public function test_settings_ai_page_never_renders_an_api_secret(): void
    {
        config(['ai.api_key' => 'sk-secret-must-not-appear']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('settings.section', 'ai'))
            ->assertOk()
            ->assertDontSee('sk-secret-must-not-appear')
            ->assertSee('A key is present.');
    }

    public function test_granular_ai_permissions_and_seeded_roles(): void
    {
        $overview = $this->userWithPermissions(['ai.overview.view']);
        $finance = $this->userWithPermissions(['ai.finance.use']);

        $this->actingAs($overview)->get(route('ai.overview'))->assertOk();
        $this->actingAs($overview)->get(route('ai.finance'))->assertForbidden();
        $this->actingAs($finance)->get(route('ai.finance'))->assertOk();
        $this->actingAs($finance)->get(route('ai.overview'))->assertForbidden();

        $this->seed();
        $this->assertTrue(\App\Models\Role::query()->where('slug', 'sales-manager')->first()->permissions()->where('name', 'ai.leads.use')->exists());
        $this->assertTrue(\App\Models\Role::query()->where('slug', 'support-executive')->first()->permissions()->where('name', 'ai.tickets.use')->exists());
        $this->assertTrue(\App\Models\Role::query()->where('slug', 'accountant')->first()->permissions()->where('name', 'ai.finance.use')->exists());
        $this->assertFalse(\App\Models\Role::query()->where('slug', 'employee')->first()->permissions()->where('name', 'ai.overview.view')->exists());
    }

    public function test_record_assistants_require_both_ai_and_record_permissions(): void
    {
        $this->enableAi();
        $lead = Lead::factory()->create();
        $project = Project::factory()->create();
        $requirement = Requirement::factory()->create(['project_id' => $project->id]);
        $quotation = Quotation::factory()->create();
        $ticket = Ticket::factory()->create();

        $aiOnly = $this->userWithPermissions(['ai.leads.use', 'ai.projects.use', 'ai.requirements.use', 'ai.quotations.use', 'ai.tickets.use']);
        $recordOnly = $this->userWithPermissions(['leads.view', 'projects.view', 'requirements.view', 'quotations.view', 'tickets.view']);

        Livewire::actingAs($aiOnly)->test(LeadPanel::class, ['leadId' => $lead->id])->assertForbidden();
        Livewire::actingAs($recordOnly)->test(LeadPanel::class, ['leadId' => $lead->id])->assertForbidden();
        Livewire::actingAs($aiOnly)->test(ProjectPanel::class, ['projectId' => $project->id])->assertForbidden();
        Livewire::actingAs($recordOnly)->test(ProjectPanel::class, ['projectId' => $project->id])->assertForbidden();
        Livewire::actingAs($aiOnly)->test(RequirementPanel::class, ['requirementId' => $requirement->id])->assertForbidden();
        Livewire::actingAs($recordOnly)->test(RequirementPanel::class, ['requirementId' => $requirement->id])->assertForbidden();
        Livewire::actingAs($aiOnly)->test(QuotationPanel::class, ['quotationId' => $quotation->id])->assertForbidden();
        Livewire::actingAs($recordOnly)->test(QuotationPanel::class, ['quotationId' => $quotation->id])->assertForbidden();
        Livewire::actingAs($aiOnly)->test(TicketPanel::class, ['ticketId' => $ticket->id])->assertForbidden();
        Livewire::actingAs($recordOnly)->test(TicketPanel::class, ['ticketId' => $ticket->id])->assertForbidden();
    }

    public function test_authorized_staff_can_mount_record_panels(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions([
            'ai.leads.use', 'leads.view',
            'ai.projects.use', 'projects.view',
            'ai.requirements.use', 'requirements.view',
            'ai.quotations.use', 'quotations.view',
            'ai.tickets.use', 'tickets.view',
            'ai.overview.view', 'ai.finance.use',
        ]);
        $lead = Lead::factory()->create();
        $project = Project::factory()->create();
        $requirement = Requirement::factory()->create(['project_id' => $project->id]);
        $quotation = Quotation::factory()->create();
        $ticket = Ticket::factory()->create();

        Livewire::actingAs($user)->test(LeadPanel::class, ['leadId' => $lead->id])->assertOk();
        Livewire::actingAs($user)->test(ProjectPanel::class, ['projectId' => $project->id])->assertOk();
        Livewire::actingAs($user)->test(RequirementPanel::class, ['requirementId' => $requirement->id])->assertOk();
        Livewire::actingAs($user)->test(QuotationPanel::class, ['quotationId' => $quotation->id])->assertOk();
        Livewire::actingAs($user)->test(TicketPanel::class, ['ticketId' => $ticket->id])->assertOk();
        Livewire::actingAs($user)->test(CompanyOverview::class)->assertOk();
        Livewire::actingAs($user)->test(FinanceInsights::class)->assertOk();
    }
}
