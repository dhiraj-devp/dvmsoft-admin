<?php

namespace Tests\Feature\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TicketPriority;
use App\Livewire\Reports\Panel;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\FinanceMetricsService;
use App\Services\ProjectProfitabilityService;
use App\Services\Reports\FinanceReportService;
use App\Services\Reports\ProjectReportService;
use App\Services\Reports\SalesReportService;
use App\Support\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_metrics_use_existing_lead_and_quotation_records(): void
    {
        $owner = User::factory()->create();
        Lead::factory()->count(2)->create([
            'assigned_user_id' => $owner->id,
            'status' => LeadStatus::New,
            'source' => 'website',
            'estimated_value' => 10000,
        ]);
        Lead::factory()->create([
            'assigned_user_id' => $owner->id,
            'status' => LeadStatus::Won,
            'converted_at' => now(),
            'estimated_value' => 50000,
            'source' => 'referral',
        ]);
        Lead::factory()->lost()->create([
            'assigned_user_id' => $owner->id,
            'estimated_value' => 8000,
            'source' => 'website',
        ]);
        Lead::factory()->lost()->create([
            'status' => LeadStatus::Lost,
            'created_at' => now()->subYear(),
            'estimated_value' => 999999,
        ]);

        $period = ReportPeriod::resolve('this_month');
        $report = app(SalesReportService::class)->build($period);

        $this->assertSame(4, $report['kpis']['leads']);
        $this->assertSame(50000.0, $report['kpis']['won_revenue']);
        $this->assertSame(25.0, $report['kpis']['conversion_rate']);
        $this->assertSame(20000.0, $report['kpis']['pipeline_value']);
    }

    public function test_finance_period_totals_match_existing_invoice_payment_and_expense_math(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->sent()->create([
            'client_id' => $client->id,
            'invoice_date' => now()->toDateString(),
            'total' => 50000,
            'amount_paid' => 20000,
            'balance' => 30000,
            'status' => InvoiceStatus::Sent,
        ]);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 20000,
            'paid_on' => now()->toDateString(),
        ]);
        Expense::factory()->create([
            'expense_date' => now()->toDateString(),
            'amount' => 8000,
            'tax_amount' => 1440,
        ]);
        Invoice::factory()->sent()->create([
            'invoice_date' => now()->subYear()->toDateString(),
            'total' => 111,
            'amount_paid' => 111,
            'balance' => 0,
        ]);

        $period = ReportPeriod::resolve('this_month');
        $report = app(FinanceReportService::class)->build($period);
        $dashboard = app(FinanceMetricsService::class)->dashboard();

        $this->assertSame(50000.0, $report['kpis']['revenue']);
        $this->assertSame(20000.0, $report['kpis']['collected']);
        $this->assertSame(9440.0, $report['kpis']['expenses']);
        $this->assertSame(10560.0, $report['kpis']['profit']);
        $this->assertSame($dashboard['metrics'][6]['value'], money($report['kpis']['revenue']));
    }

    public function test_project_profitability_reuses_existing_finance_service(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->active()->create([
            'client_id' => $client->id,
            'budget' => 100000,
            'health' => ProjectHealth::Yellow,
            'expected_end_date' => now()->subDay()->toDateString(),
            'status' => ProjectStatus::Active,
        ]);
        Invoice::factory()->sent()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'total' => 50000,
            'amount_paid' => 20000,
            'balance' => 30000,
        ]);
        Expense::factory()->create([
            'project_id' => $project->id,
            'amount' => 8000,
            'tax_amount' => 1440,
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Todo,
        ]);

        $expected = app(ProjectProfitabilityService::class)->forProject($project);
        $report = app(ProjectReportService::class)->build(ReportPeriod::resolve('this_month'));
        $export = collect($report['tables'][1]['export'])->first();

        $this->assertSame($expected['budget'], $export[2]);
        $this->assertSame($expected['invoiced'], $export[3]);
        $this->assertSame($expected['paid'], $export[4]);
        $this->assertSame($expected['expenses'], $export[5]);
        $this->assertSame($expected['actual_profit'], $export[7]);
        $this->assertSame(1, $report['kpis']['active_projects']);
        $this->assertSame(1, $report['metrics'][2]['value']);
    }

    public function test_csv_export_is_authorized_and_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Lead::factory()->create(['status' => LeadStatus::New]);

        $response = $this->actingAs($admin)->get(route('reports.export', [
            'section' => 'sales',
            'preset' => 'this_month',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Sales performance by user', $response->streamedContent());
        $this->assertTrue(AuditLog::query()->where('module', 'reports')->where('action', 'exported')->exists());
    }

    public function test_hr_report_does_not_expose_payroll_or_salary(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('reports.hr'))
            ->assertOk()
            ->assertSee('Total employees')
            ->assertDontSee('salary')
            ->assertDontSee('payroll')
            ->assertDontSee('Salary');
    }

    public function test_date_range_filter_changes_visible_rows(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Ticket::factory()->create([
            'subject' => 'Current ticket',
            'priority' => TicketPriority::High,
            'created_at' => now(),
        ]);
        Ticket::factory()->create([
            'subject' => 'Old ticket',
            'created_at' => now()->subYear(),
        ]);

        $this->actingAs($admin);

        Livewire::test(Panel::class, ['section' => 'support'])
            ->set('preset', 'this_month')
            ->assertSee('Tickets opened')
            ->assertSee('1');
    }

    public function test_custom_range_and_existing_finance_reports_route_still_work(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('finance.reports'))
            ->assertOk()
            ->assertSee('Project profitability');

        Livewire::test(Panel::class, ['section' => 'finance'])
            ->set('preset', 'custom')
            ->set('from', now()->startOfYear()->toDateString())
            ->set('to', now()->toDateString())
            ->assertSee('Revenue');
    }
}
