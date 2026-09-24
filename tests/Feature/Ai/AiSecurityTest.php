<?php

namespace Tests\Feature\Ai;

use App\Ai\Providers\FakeAiProvider;
use App\Livewire\Ai\LeadPanel;
use App\Livewire\Ai\TicketPanel;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\Ai\TicketAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_secrets_are_stripped_before_the_provider_is_called(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.leads.use', 'leads.view']);
        $lead = Lead::factory()->create([
            'notes' => 'password=supersecret api_key=sk-abcdefghijklmnopqrstuvwxyz',
            'requirement' => 'Build a portal. Bearer abc.def.ghi',
        ]);

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary')
            ->assertSet('generated', true);

        $prompt = FakeAiProvider::$calls[0]['prompt'] ?? '';

        $this->assertStringNotContainsString('supersecret', $prompt);
        $this->assertStringNotContainsString('sk-abcdefghijklmnopqrstuvwxyz', $prompt);
        $this->assertStringNotContainsString('abc.def.ghi', $prompt);
        $this->assertStringContainsString('[redacted', $prompt);
    }

    public function test_internal_ticket_notes_are_excluded_from_draft_context_without_permission(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.tickets.use', 'tickets.view']);
        $ticket = Ticket::factory()->create();
        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'body' => 'Public client message',
            'is_internal' => false,
        ]);
        TicketMessage::factory()->internal()->create([
            'ticket_id' => $ticket->id,
            'body' => 'SECRET_INTERNAL_NOTE',
            'is_internal' => true,
        ]);

        $context = app(TicketAssistant::class)->context($ticket->fresh('messages'), $user);

        $this->assertSame([], $context['internal_notes']);
        $this->assertTrue(collect($context['public_messages'])->contains(fn ($row) => $row['body'] === 'Public client message'));
        $this->assertFalse(collect($context['public_messages'])->contains(fn ($row) => str_contains($row['body'], 'SECRET_INTERNAL_NOTE')));
    }

    public function test_internal_notes_never_go_into_the_canned_draft_reply_and_are_not_sent(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.tickets.use', 'tickets.view', 'tickets.reply', 'tickets.internal_notes']);
        $ticket = Ticket::factory()->create();
        TicketMessage::factory()->internal()->create([
            'ticket_id' => $ticket->id,
            'body' => 'SECRET_INTERNAL_NOTE',
        ]);

        $component = Livewire::actingAs($user)
            ->test(TicketPanel::class, ['ticketId' => $ticket->id])
            ->call('draftReply')
            ->assertSet('generated', true);

        $this->assertStringNotContainsString('SECRET_INTERNAL_NOTE', (string) data_get($component->get('result'), 'draft_reply'));
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_audit_log_does_not_store_the_prompt_or_response(): void
    {
        $this->enableAi();
        $user = $this->userWithPermissions(['ai.leads.use', 'leads.view']);
        $lead = Lead::factory()->create(['requirement' => 'Confidential requirement text']);

        Livewire::actingAs($user)
            ->test(LeadPanel::class, ['leadId' => $lead->id])
            ->call('generateSummary');

        $log = AuditLog::query()->where('module', 'ai')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('generated', $log->action);
        $this->assertSame(['feature' => 'lead', 'success' => true], $log->new_values);
        $this->assertStringNotContainsString('Confidential requirement text', json_encode($log->new_values));
        $this->assertStringNotContainsString('Confidential requirement text', json_encode($log->old_values));
    }
}
