<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\InvoiceBalanceService;
use App\Services\PaymentService;
use App\Services\ProjectProfitabilityService;
use App\Services\QuotationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_totals_use_the_same_calculator_as_quotations(): void
    {
        $result = app(QuotationCalculator::class)->calculate([
            [
                'description' => 'Sprint',
                'quantity' => 2,
                'unit_price' => 1000,
                'discount_percent' => 10,
                'tax_percent' => 18,
            ],
        ], 5);

        $this->assertSame(2034.0, $result['total']);
    }

    public function test_payments_update_invoice_status_to_partial_then_paid(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $invoice = Invoice::factory()->sent()->withItem()->create([
            'total' => 11800,
            'balance' => 11800,
            'amount_paid' => 0,
        ]);
        $payments = app(PaymentService::class);

        $payments->record($invoice, [
            'amount' => 5000,
            'paid_on' => now()->toDateString(),
            'method' => 'upi',
            'reference' => 'UPI1',
            'notes' => '',
        ], $admin);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame('5000.00', (string) $invoice->amount_paid);
        $this->assertSame('6800.00', (string) $invoice->balance);

        $payments->record($invoice->fresh(), [
            'amount' => 6800,
            'paid_on' => now()->toDateString(),
            'method' => 'bank_transfer',
            'reference' => 'NEFT1',
            'notes' => '',
        ], $admin);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('0.00', (string) $invoice->balance);
        $this->assertTrue(
            AuditLog::query()->where('module', 'payments')->where('action', 'created')->exists()
        );
    }

    public function test_outstanding_balance_and_days_overdue_are_calculated(): void
    {
        $invoice = Invoice::factory()->sent()->withItem()->create([
            'due_date' => now()->subDays(4)->toDateString(),
            'total' => 11800,
            'balance' => 11800,
            'amount_paid' => 0,
        ]);

        app(InvoiceBalanceService::class)->refresh($invoice);

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Overdue, $invoice->status);
        $this->assertSame(4, $invoice->daysOverdue());
        $this->assertTrue(Invoice::query()->outstanding()->whereKey($invoice)->exists());
    }

    public function test_invoice_and_receipt_pdfs_are_generated(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $invoice = Invoice::factory()->sent()->withItem()->create(['title' => 'Platform build']);
        $payment = \App\Models\Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => 1000,
        ]);
        app(InvoiceBalanceService::class)->refresh($invoice);

        $invoicePdf = $this->actingAs($admin)->get(route('invoices.pdf', $invoice));
        $invoicePdf->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $invoicePdf->headers->get('content-type')));
        $this->assertStringContainsString('%PDF', $invoicePdf->getContent());

        $receiptPdf = $this->actingAs($admin)->get(route('payments.pdf', $payment));
        $receiptPdf->assertOk();
        $this->assertStringContainsString('%PDF', $receiptPdf->getContent());
    }

    public function test_invoice_from_project_reuses_client_and_quotation_ids(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create();
        $quotation = Quotation::factory()->converted()->withItem()->create([
            'client_id' => $client->id,
            'title' => 'Delivery',
        ]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'quotation_id' => $quotation->id,
            'name' => 'Delivery',
            'budget' => 11800,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.invoice', $project))
            ->assertRedirect();

        $invoice = Invoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame($client->id, $invoice->client_id);
        $this->assertSame($project->id, $invoice->project_id);
        $this->assertSame($quotation->id, $invoice->quotation_id);
        $this->assertTrue(str_starts_with($invoice->number, 'INV-'.now()->year.'-'));
        $this->assertSame(1, Client::query()->count());
        $this->assertTrue(
            AuditLog::query()->where('module', 'invoices')->where('action', 'created')->exists()
        );
    }

    public function test_project_profitability_uses_invoiced_paid_and_expenses(): void
    {
        $client = Client::factory()->create();
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'budget' => 100000,
        ]);
        $invoice = Invoice::factory()->sent()->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'total' => 50000,
            'amount_paid' => 20000,
            'balance' => 30000,
        ]);
        \App\Models\Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 20000,
        ]);
        \App\Models\Expense::factory()->create([
            'project_id' => $project->id,
            'amount' => 8000,
            'tax_amount' => 1440,
        ]);

        app(InvoiceBalanceService::class)->refresh($invoice);

        $row = app(ProjectProfitabilityService::class)->forProject($project);

        $this->assertSame(100000.0, $row['budget']);
        $this->assertSame(50000.0, $row['invoiced']);
        $this->assertSame(20000.0, $row['paid']);
        $this->assertSame(9440.0, $row['expenses']);
        $this->assertSame(90560.0, $row['estimated_profit']);
        $this->assertSame(10560.0, $row['actual_profit']);
    }

    public function test_sending_a_draft_invoice_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $invoice = Invoice::factory()->withItem()->create();

        $this->actingAs($admin)
            ->post(route('invoices.send', $invoice))
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Sent, $invoice->fresh()->status);
        $this->assertTrue(
            AuditLog::query()->where('module', 'invoices')->where('action', 'updated')->exists()
        );
    }

    public function test_reminder_command_records_overdue_reminders_without_whatsapp(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();
        $invoice = Invoice::factory()->sent()->withItem()->create([
            'due_date' => now()->subDays(2)->toDateString(),
            'total' => 11800,
            'balance' => 11800,
            'amount_paid' => 0,
        ]);
        app(InvoiceBalanceService::class)->refresh($invoice);

        $this->artisan('finance:send-invoice-reminders')->assertSuccessful();

        $reminder = InvoiceReminder::query()->first();
        $this->assertNotNull($reminder);
        $this->assertSame('overdue', $reminder->type);
        $this->assertFalse($reminder->meta['whatsapp']);
        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);
    }
}
