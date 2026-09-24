<?php

namespace Tests\Feature\Ai;

use App\Ai\Exceptions\AiException;
use App\Ai\Exceptions\AiTimeoutException;
use App\Ai\Providers\FakeAiProvider;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectPriority;
use App\Enums\RequirementApprovalStatus;
use App\Enums\TaskStatus;
use App\Livewire\Ai\CompanyOverview;
use App\Livewire\Ai\FinanceInsights;
use App\Livewire\Ai\LeadPanel;
use App\Livewire\Ai\ProjectPanel;
use App\Livewire\Ai\QuotationPanel;
use App\Livewire\Ai\RequirementPanel;
use App\Livewire\Ai\TicketPanel;
use App\Livewire\Quotations\Form as QuotationForm;
use App\Livewire\Tickets\Conversation;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiAssistantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_assistant_returns_structured_suggestions_without_changing_status(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.leads.use', 'leads.view']);
        $lead = Lead::factory()->create(['status' => LeadStatus::New, 'notes' => 'Need a CRM']);

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary')
            ->assertSet('generated', true)
            ->assertSet('error', null)
            ->assertSee('Suggested qualification status');

        $this->assertSame(LeadStatus::New, $lead->fresh()->status);
        $this->assertNotEmpty(FakeAiProvider::$calls);
    }

    public function test_requirement_analysis_is_not_saved_until_apply(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.requirements.use', 'requirements.view', 'requirements.edit', 'projects.view']);
        $requirement = Requirement::factory()->create([
            'description' => 'Original description',
            'notes' => null,
            'priority' => ProjectPriority::Low,
            'client_approval_status' => RequirementApprovalStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(RequirementPanel::class, ['requirementId' => $requirement->id])
            ->call('analyzeRequirement')
            ->assertSet('generated', true);

        $this->assertSame('Original description', $requirement->fresh()->description);
        $this->assertSame(RequirementApprovalStatus::Pending, $requirement->fresh()->client_approval_status);

        Livewire::actingAs($user)
            ->test(RequirementPanel::class, ['requirementId' => $requirement->id])
            ->call('analyzeRequirement')
            ->call('applyAnalysis')
            ->assertSet('applied', true);

        $fresh = $requirement->fresh();
        $this->assertNotSame('Original description', $fresh->description);
        $this->assertSame(RequirementApprovalStatus::Pending, $fresh->client_approval_status);
        $this->assertNotNull($fresh->notes);
    }

    public function test_quotation_apply_fills_descriptions_without_changing_prices_or_totals(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.quotations.use', 'quotations.view', 'quotations.edit', 'quotations.create', 'clients.view']);
        $quotation = Quotation::factory()->create([
            'title' => 'Original title',
            'notes' => 'Keep me',
            'subtotal' => 10000,
            'total' => 11800,
        ]);
        $quotation->items()->create([
            'description' => 'Original item',
            'quantity' => 2,
            'unit_price' => 5000,
            'discount_percent' => 0,
            'tax_percent' => 18,
            'line_total' => 11800,
            'sort_order' => 0,
        ]);

        FakeAiProvider::queue([
            'title' => 'AI title',
            'line_item_descriptions' => ['Rewritten scope item', 'Should not add a priced row'],
            'missing_pricing_information' => ['Confirm hours'],
            'payment_terms_wording' => '50% advance on acceptance.',
        ]);

        $component = Livewire::actingAs($user)
            ->test(QuotationForm::class, ['quotation' => $quotation])
            ->call('applyAiSuggestions', 'AI title', '50% advance on acceptance.', ['Rewritten scope item', 'Should not add a priced row']);

        $this->assertSame('AI title', $component->get('form.title'));
        $this->assertSame('Rewritten scope item', $component->get('items.0.description'));
        $this->assertEquals(5000, $component->get('items.0.unit_price'));
        $this->assertEquals(11800, $component->get('totals.total'));
        $this->assertCount(1, $component->get('items'));

        $this->assertSame('Original title', $quotation->fresh()->title);
        $this->assertEquals(11800, (float) $quotation->fresh()->total);
        $this->assertSame('Original item', $quotation->fresh()->items()->first()->description);
        $this->assertEquals(5000, (float) $quotation->fresh()->items()->first()->unit_price);
        $this->assertSame(1, $quotation->fresh()->items()->count());
    }

    public function test_project_assistant_uses_existing_task_data(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.projects.use', 'projects.view']);
        $project = Project::factory()->create();
        Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Overdue design',
            'status' => TaskStatus::Todo,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(ProjectPanel::class, ['projectId' => $project->id])
            ->call('generateSummary')
            ->assertSet('generated', true)
            ->assertSee('Status summary');
    }

    public function test_ticket_assistant_does_not_send_a_reply(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.tickets.use', 'tickets.view', 'tickets.reply']);
        $ticket = Ticket::factory()->create();
        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'body' => 'Client-visible issue',
            'is_internal' => false,
        ]);

        Livewire::actingAs($user)
            ->test(TicketPanel::class, ['ticketId' => $ticket->id])
            ->call('draftReply')
            ->assertSet('generated', true)
            ->call('fillReply')
            ->assertDispatched('ai-draft-reply');

        $this->assertSame(1, $ticket->messages()->count());

        Livewire::actingAs($user)
            ->test(Conversation::class, ['ticketId' => $ticket->id])
            ->call('fillFromAi', 'Draft only')
            ->assertSet('body', 'Draft only')
            ->assertSet('internal', false);

        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_finance_and_overview_insights_do_not_modify_financial_records(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions([
            'ai.finance.use',
            'ai.overview.view',
            'invoices.view',
            'reports.finance.view',
            'reports.sales.view',
            'reports.projects.view',
            'reports.support.view',
        ]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Overdue,
            'due_date' => now()->subDays(10)->toDateString(),
            'balance' => 11800,
        ]);

        Livewire::actingAs($user)
            ->test(FinanceInsights::class)
            ->call('generateInsights')
            ->assertSet('generated', true)
            ->assertSee('not accounting or legal advice', false);

        Livewire::actingAs($user)
            ->test(CompanyOverview::class)
            ->call('generateInsights')
            ->assertSet('generated', true);

        $this->assertEquals(11800, (float) $invoice->fresh()->balance);
        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);
    }

    public function test_provider_failure_and_timeout_are_shown_without_writing_records(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.leads.use', 'leads.view']);
        $lead = Lead::factory()->create(['status' => LeadStatus::Contacted]);

        FakeAiProvider::$failWith = new AiException('boom');

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary')
            ->assertSet('generated', false)
            ->assertSet('error', 'AI assistance is unavailable right now.');

        FakeAiProvider::reset();
        FakeAiProvider::$failWith = new AiTimeoutException('slow');

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary')
            ->assertSet('error', 'The AI provider timed out. Try again.');

        $this->assertSame(LeadStatus::Contacted, $lead->fresh()->status);
    }

    public function test_disabled_ai_fails_gracefully(): void
    {
        config(['ai.enabled' => false]);
        settings()->set('ai.enabled', false);
        $user = $this->userWithPermissions(['ai.leads.use', 'leads.view']);
        $lead = Lead::factory()->create();

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary')
            ->assertSet('error', 'AI assistance is turned off in Settings.');
    }
}
