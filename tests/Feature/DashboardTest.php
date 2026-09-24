<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\TicketStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_dashboard_shows_live_module_counts(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Lead::factory()->create(['status' => LeadStatus::New]);
        Project::factory()->create(['status' => ProjectStatus::Active]);
        Invoice::factory()->create([
            'status' => InvoiceStatus::Sent,
            'total' => 11800,
            'balance' => 11800,
        ]);
        Ticket::factory()->create(['status' => TicketStatus::Open]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active Projects')
            ->assertSee('New Leads')
            ->assertSee('Open Tickets')
            ->assertDontSee('Projects module not enabled yet')
            ->assertSee('Planning, active, and on hold')
            ->assertSee('Waiting first contact');
    }

    public function test_home_dashboard_hides_finance_figures_without_invoice_permission(): void
    {
        $user = $this->userWithPermissions(['dashboard.view']);

        Invoice::factory()->create([
            'status' => InvoiceStatus::Sent,
            'total' => 50000,
            'balance' => 50000,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Finance access required')
            ->assertDontSee('50,000');
    }
}
