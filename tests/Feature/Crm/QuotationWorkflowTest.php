<?php

namespace Tests\Feature\Crm;

use App\Enums\QuotationStatus;
use App\Models\AuditLog;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_download_is_generated_and_marks_sent_quotations_as_viewed(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $quotation = Quotation::factory()->sent()->withItem()->create([
            'title' => 'Brand website rebuild',
        ]);

        $response = $this->actingAs($admin)->get(route('quotations.pdf', $quotation));

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $response->headers->get('content-type')));
        $this->assertStringContainsString('%PDF', $response->getContent());
        $this->assertSame(QuotationStatus::Viewed, $quotation->fresh()->status);
    }

    public function test_quotation_moves_through_send_accept_and_convert(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $quotation = Quotation::factory()->withItem()->create();
        $workflow = app(QuotationWorkflowService::class);

        $workflow->send($quotation, $admin);
        $this->assertSame(QuotationStatus::Sent, $quotation->fresh()->status);

        $this->actingAs($admin)
            ->post(route('quotations.accept', $quotation->fresh()))
            ->assertRedirect();
        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);

        $this->actingAs($admin)
            ->post(route('quotations.convert', $quotation->fresh()))
            ->assertRedirect();

        $quotation->refresh();
        $this->assertSame(QuotationStatus::Converted, $quotation->status);
        $this->assertNotNull($quotation->converted_at);
        $this->assertTrue(
            AuditLog::query()->where('module', 'quotations')->where('action', 'updated')->exists()
        );
    }

    public function test_users_without_approve_permission_cannot_accept_quotations(): void
    {
        $user = User::factory()->create();
        $quotation = Quotation::factory()->sent()->create();

        $this->actingAs($user)
            ->post(route('quotations.accept', $quotation))
            ->assertForbidden();
    }
}
