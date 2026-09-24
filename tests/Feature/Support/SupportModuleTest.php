<?php

namespace Tests\Feature\Support;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketSlaRule;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use App\Services\TicketSlaService;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_use_sequential_numbers_and_existing_client_project_ids(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->seed(\Database\Seeders\SupportSeeder::class);
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $clientsBefore = Client::query()->count();
        $projectsBefore = Project::query()->count();

        $ticket = app(TicketWorkflowService::class)->create([
            'client_id' => $client->id,
            'project_id' => $project->id,
            'category_id' => '',
            'assigned_to_id' => '',
            'subject' => 'Login failing',
            'description' => 'Cannot sign in to the portal.',
            'priority' => TicketPriority::Normal->value,
        ], $admin);

        $this->assertTrue(str_starts_with($ticket->number, 'TKT-'.now()->year.'-'));
        $this->assertSame($client->id, $ticket->client_id);
        $this->assertSame($project->id, $ticket->project_id);
        $this->assertSame($clientsBefore, Client::query()->count());
        $this->assertSame($projectsBefore, Project::query()->count());
        $this->assertTrue(AuditLog::query()->where('module', 'tickets')->where('action', 'created')->exists());
    }

    public function test_internal_notes_are_hidden_without_permission(): void
    {
        Storage::fake('support');
        $admin = User::factory()->superAdmin()->create();
        $ticket = Ticket::factory()->create();
        $message = TicketMessage::factory()->internal()->create([
            'ticket_id' => $ticket->id,
            'author_id' => $admin->id,
            'body' => 'SECRET_INTERNAL_NOTE',
        ]);
        $path = UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')->store('tickets/'.$ticket->id, 'support');
        $attachment = TicketAttachment::query()->create([
            'ticket_message_id' => $message->id,
            'uploaded_by_id' => $admin->id,
            'original_name' => 'note.pdf',
            'path' => $path,
            'disk' => 'support',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('SECRET_INTERNAL_NOTE');

        $viewer = $this->userWithPermissions(['tickets.view']);
        $this->actingAs($viewer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('SECRET_INTERNAL_NOTE');

        $this->actingAs($viewer)
            ->get(route('tickets.attachments.download', [$ticket, $message, $attachment]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('tickets.attachments.download', [$ticket, $message, $attachment]))
            ->assertOk();
    }

    public function test_sla_deadline_remaining_time_and_breach(): void
    {
        TicketSlaRule::factory()->create([
            'priority' => TicketPriority::High,
            'hours' => 8,
            'warning_hours' => 2,
        ]);

        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Open,
            'created_at' => now(),
        ]);
        $due = app(TicketSlaService::class)->dueAt(TicketPriority::High, now());
        $ticket->update(['sla_due_at' => $due]);

        $this->assertEqualsWithDelta(8 * 3600, $ticket->fresh()->slaRemainingSeconds(), 5);
        $this->assertFalse($ticket->fresh()->isSlaBreached());
        $this->assertStringContainsString('remaining', $ticket->fresh()->slaLabel());

        $ticket->update(['sla_due_at' => now()->subHour()]);
        $this->assertTrue($ticket->fresh()->isSlaBreached());
        $this->assertStringContainsString('Breached', $ticket->fresh()->slaLabel());
    }

    public function test_ticket_workflow_notifications_and_status_changes(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $assignee = User::factory()->superAdmin()->create();
        $this->seed(\Database\Seeders\SupportSeeder::class);
        $client = Client::factory()->create();
        $workflow = app(TicketWorkflowService::class);

        $ticket = $workflow->create([
            'client_id' => $client->id,
            'project_id' => '',
            'category_id' => '',
            'assigned_to_id' => $assignee->id,
            'subject' => 'Billing question',
            'description' => 'Invoice mismatch',
            'priority' => TicketPriority::Normal->value,
        ], $admin);

        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        Notification::assertSentTo($assignee, TicketEventNotification::class);

        $workflow->reply($ticket, $admin, 'Looking into this.');
        $this->assertTrue(AuditLog::query()->where('module', 'tickets')->where('action', 'created')->exists());

        $workflow->setStatus($ticket->fresh(), TicketStatus::WaitingForClient);
        $this->assertSame(TicketStatus::WaitingForClient, $ticket->fresh()->status);

        $workflow->resolve($ticket->fresh(), $admin, 'Corrected the invoice.');
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);
        Notification::assertSentTo($assignee, TicketEventNotification::class, function (TicketEventNotification $notification) {
            return $notification->event === 'resolved';
        });

        $workflow->close($ticket->fresh(), $admin);
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertTrue(AuditLog::query()->where('module', 'tickets')->where('action', 'updated')->exists());
    }

    public function test_sla_command_notifies_approaching_and_breached(): void
    {
        Notification::fake();
        $assignee = User::factory()->superAdmin()->create();
        TicketSlaRule::factory()->create([
            'priority' => TicketPriority::Urgent,
            'hours' => 2,
            'warning_hours' => 1,
        ]);

        $approaching = Ticket::factory()->create([
            'assigned_to_id' => $assignee->id,
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::Open,
            'sla_due_at' => now()->addMinutes(30),
        ]);
        $breached = Ticket::factory()->create([
            'assigned_to_id' => $assignee->id,
            'priority' => TicketPriority::Urgent,
            'status' => TicketStatus::InProgress,
            'sla_due_at' => now()->subMinutes(10),
        ]);

        $this->artisan('support:check-sla')->assertSuccessful();

        $this->assertNotNull($approaching->fresh()->sla_notified_approaching_at);
        $this->assertNotNull($breached->fresh()->sla_notified_breached_at);
        $this->assertNotNull($breached->fresh()->sla_breached_at);
        Notification::assertSentTo($assignee, TicketEventNotification::class);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Limited Support',
            'slug' => 'limited-support-'.uniqid(),
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
