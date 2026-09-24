<?php

namespace Tests\Feature\Automations;

use App\Automations\AutomationEngine;
use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Automations\Handlers\CrmAutomations;
use App\Enums\AutomationRunStatus;
use App\Enums\DocumentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Enums\TaskStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Jobs\RunAutomationJob;
use App\Models\AuditLog;
use App\Models\Automation;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Lead;
use App\Models\LeaveRequest;
use App\Models\NotificationPreference;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketSlaRule;
use App\Models\User;
use App\Notifications\ClientPortalEventNotification;
use App\Notifications\DocumentEventNotification;
use App\Notifications\FollowUpReminderNotification;
use App\Notifications\InvoiceReminderNotification;
use App\Notifications\OperationalNotification;
use App\Notifications\TicketEventNotification;
use App\Services\InvoiceBalanceService;
use App\Services\QuotationWorkflowService;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_command_still_notifies_assignees(): void
    {
        Notification::fake();
        Mail::fake();

        $assignee = User::factory()->create();
        FollowUp::factory()->create([
            'assigned_user_id' => $assignee->id,
            'status' => FollowUpStatus::Pending,
            'reminder_at' => now()->subMinute(),
            'reminded_at' => null,
        ]);

        $this->artisan('crm:send-follow-up-reminders')->assertSuccessful();

        Notification::assertSentTo($assignee, FollowUpReminderNotification::class);
        $this->assertNotNull(FollowUp::query()->first()->reminded_at);
    }

    public function test_disabled_follow_up_automation_does_not_notify(): void
    {
        Notification::fake();
        $engine = app(AutomationEngine::class);
        $engine->syncCatalog();
        $engine->record('crm.follow_up_due')->update(['enabled' => false]);

        $assignee = User::factory()->create();
        FollowUp::factory()->create([
            'assigned_user_id' => $assignee->id,
            'reminder_at' => now()->subMinute(),
            'reminded_at' => null,
        ]);

        $this->artisan('crm:send-follow-up-reminders')->assertSuccessful();

        Notification::assertNotSentTo($assignee, FollowUpReminderNotification::class);
        $this->assertSame(AutomationRunStatus::Skipped, $engine->record('crm.follow_up_due')->fresh()->last_status);
    }

    public function test_lead_follow_up_notifies_owner_once(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Lead::factory()->create([
            'assigned_user_id' => $owner->id,
            'status' => LeadStatus::New,
            'next_follow_up_at' => now()->addHours(6),
        ]);

        $engine = app(AutomationEngine::class);
        $engine->run('crm.lead_follow_up_upcoming', true);
        $engine->run('crm.lead_follow_up_upcoming', true);

        Notification::assertSentToTimes($owner, OperationalNotification::class, 1);
        Notification::assertNotSentTo($other, OperationalNotification::class);
    }

    public function test_notification_channels_respect_user_preferences(): void
    {
        Notification::fake();
        settings()->set('notifications.email_enabled', true);
        settings()->set('notifications.in_app_enabled', true);

        $owner = User::factory()->create();
        NotificationPreference::query()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'in_app_enabled' => true,
            'email_enabled' => false,
        ]);

        Lead::factory()->create([
            'assigned_user_id' => $owner->id,
            'next_follow_up_at' => now()->addHour(),
        ]);

        app(AutomationEngine::class)->run('crm.lead_follow_up_upcoming', true);

        Notification::assertSentTo($owner, OperationalNotification::class, function (OperationalNotification $notification, array $channels) {
            return $channels === ['database'];
        });
    }

    public function test_scheduled_run_queues_jobs(): void
    {
        Queue::fake();
        app(AutomationEngine::class)->syncCatalog();

        $this->artisan('automations:run', ['key' => 'crm.follow_up_due'])->assertSuccessful();

        Queue::assertPushed(RunAutomationJob::class, function (RunAutomationJob $job) {
            return $job->key === 'crm.follow_up_due';
        });
    }

    public function test_failed_handler_is_recorded_without_notification_body(): void
    {
        $this->app->bind(CrmAutomations::class, fn () => new class implements AutomationHandler
        {
            public function handle(Automation $automation): AutomationResult
            {
                throw new \RuntimeException('forced failure');
            }
        });

        $run = app(AutomationEngine::class)->run('crm.follow_up_due', true);

        $this->assertSame(AutomationRunStatus::Failed, $run->status);
        $this->assertSame('forced failure', $run->error);
        $this->assertTrue(
            AuditLog::query()->where('module', 'automations')->where('action', 'failed')->exists()
        );
        $this->assertFalse(
            AuditLog::query()->where('new_values', 'like', '%notification body%')->exists()
        );
    }

    public function test_invoice_reminder_command_still_records_overdue_without_whatsapp(): void
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
        Notification::assertSentTo(User::query()->where('is_super_admin', true)->first(), InvoiceReminderNotification::class);
    }

    public function test_sla_command_still_notifies_approaching_and_breached(): void
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
        Notification::assertSentTo($assignee, TicketEventNotification::class);
    }

    public function test_document_expiry_command_still_notifies_owners(): void
    {
        Notification::fake();
        $owner = User::factory()->superAdmin()->create();
        $document = Document::factory()->create([
            'owner_id' => $owner->id,
            'created_by_id' => $owner->id,
            'status' => DocumentStatus::Approved,
            'expiry_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->artisan('documents:notify-expiring')->assertSuccessful();

        $this->assertNotNull($document->fresh()->expiry_notified_at);
        Notification::assertSentTo($owner, DocumentEventNotification::class, function (DocumentEventNotification $notification) {
            return $notification->event === 'expiring';
        });
    }

    public function test_project_health_and_blocked_task_alerts(): void
    {
        Notification::fake();
        $manager = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create([
            'manager_id' => $manager->id,
            'status' => ProjectStatus::Active,
            'health' => ProjectHealth::Red,
            'expected_end_date' => now()->addDays(3)->toDateString(),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'assigned_user_id' => $assignee->id,
            'status' => TaskStatus::Blocked,
        ]);

        $engine = app(AutomationEngine::class);
        $engine->run('projects.health_risk', true);
        $engine->run('projects.task_blocked', true);

        Notification::assertSentTo($manager, OperationalNotification::class);
        Notification::assertSentTo($assignee, OperationalNotification::class);
    }

    public function test_hr_leave_pending_notifies_approvers(): void
    {
        Notification::fake();
        $approver = $this->userWithPermissions(['leave.approve', 'hr.dashboard.view']);
        LeaveRequest::factory()->create();

        app(AutomationEngine::class)->run('hr.leave_pending', true);

        Notification::assertSentTo($approver, OperationalNotification::class);
    }

    public function test_client_portal_quotation_is_isolated(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userA = ClientUser::factory()->create(['client_id' => $clientA->id]);
        $userB = ClientUser::factory()->create(['client_id' => $clientB->id]);
        $quotation = Quotation::factory()->withItem()->create([
            'client_id' => $clientA->id,
            'status' => QuotationStatus::Draft,
        ]);

        app(QuotationWorkflowService::class)->send($quotation, $admin);

        Notification::assertSentTo($userA, ClientPortalEventNotification::class);
        Notification::assertNotSentTo($userB, ClientPortalEventNotification::class);
    }

    public function test_internal_ticket_notes_are_not_sent_to_clients(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $ticket = Ticket::factory()->create(['created_by_id' => $admin->id]);
        $portalUser = ClientUser::factory()->create(['client_id' => $ticket->client_id]);

        $workflow = app(TicketWorkflowService::class);
        $workflow->reply($ticket, $admin, 'SECRET INTERNAL NOTE', true);

        Notification::assertNotSentTo($portalUser, ClientPortalEventNotification::class);

        $workflow->reply($ticket->fresh(), $admin, 'Public client-safe reply', false);

        Notification::assertSentTo($portalUser, ClientPortalEventNotification::class, function (ClientPortalEventNotification $notification) {
            return ! str_contains($notification->message, 'SECRET INTERNAL NOTE')
                && ! str_contains($notification->message, 'Public client-safe reply');
        });
    }

    public function test_disabled_portal_automation_skips_client_notify(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $engine = app(AutomationEngine::class);
        $engine->syncCatalog();
        $engine->record('portal.quotation_decision')->update(['enabled' => false]);

        $client = Client::factory()->create();
        $portalUser = ClientUser::factory()->create(['client_id' => $client->id]);
        $quotation = Quotation::factory()->withItem()->create([
            'client_id' => $client->id,
            'status' => QuotationStatus::Draft,
        ]);

        app(QuotationWorkflowService::class)->send($quotation, $admin);

        Notification::assertNotSentTo($portalUser, ClientPortalEventNotification::class);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notifications.preferences.update'), [
                'in_app_enabled' => '0',
                'email_enabled' => '1',
            ])
            ->assertRedirect();

        $preference = NotificationPreference::for($user->fresh());
        $this->assertFalse($preference->in_app_enabled);
        $this->assertTrue($preference->email_enabled);
    }
}
