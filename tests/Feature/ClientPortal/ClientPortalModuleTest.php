<?php

namespace Tests\Feature\ClientPortal;

use App\Enums\ChangeRequestStatus;
use App\Enums\QuotationStatus;
use App\Enums\RequirementApprovalStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\ClientPortalUsers\Index;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\ResetClientPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPortalModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_login_and_logout_with_audit_log(): void
    {
        $user = ClientUser::factory()->create([
            'password' => 'password',
        ]);

        $this->get(route('client.login'))->assertOk()->assertSee('Dvmsoft Client Portal');

        $this->post(route('client.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));

        $this->assertAuthenticated('client');
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'login')->where('client_user_id', $user->id)->exists()
        );

        $this->actingAs($user, 'client')->get(route('client.dashboard'))->assertOk();

        $this->actingAs($user, 'client')
            ->post(route('client.logout'))
            ->assertRedirect(route('client.login'));

        $this->assertGuest('client');
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'logout')->where('client_user_id', $user->id)->exists()
        );
    }

    public function test_dashboard_shows_client_kpis_without_internal_employee_data(): void
    {
        $manager = User::factory()->create(['name' => 'SECRET_STAFF_MANAGER']);
        $user = ClientUser::factory()->create();
        Project::factory()->active()->create([
            'client_id' => $user->client_id,
            'manager_id' => $manager->id,
            'budget' => 999999,
            'notes' => 'SECRET_INTERNAL_NOTES',
            'name' => 'Visible Portal Project',
        ]);
        Quotation::factory()->sent()->create(['client_id' => $user->client_id]);
        Invoice::factory()->sent()->create(['client_id' => $user->client_id, 'balance' => 5000]);
        Ticket::factory()->create(['client_id' => $user->client_id]);

        $this->actingAs($user, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Active projects')
            ->assertSee('Visible Portal Project')
            ->assertDontSee('SECRET_STAFF_MANAGER')
            ->assertDontSee('SECRET_INTERNAL_NOTES')
            ->assertDontSee('999999');
    }

    public function test_projects_show_progress_milestones_and_approved_requirements_only(): void
    {
        $user = ClientUser::factory()->create();
        $project = Project::factory()->active()->create([
            'client_id' => $user->client_id,
            'budget' => 888888,
            'notes' => 'INTERNAL_PM_NOTES',
        ]);
        $project->milestones()->create([
            'name' => 'Discovery',
            'status' => 'pending',
            'completion_percentage' => 20,
        ]);
        Requirement::factory()->create([
            'project_id' => $project->id,
            'title' => 'APPROVED_REQUIREMENT',
            'client_approval_status' => RequirementApprovalStatus::Approved,
        ]);
        Requirement::factory()->create([
            'project_id' => $project->id,
            'title' => 'PENDING_REQUIREMENT',
            'client_approval_status' => RequirementApprovalStatus::Pending,
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.projects.show', $project))
            ->assertOk()
            ->assertSee('Discovery')
            ->assertSee('APPROVED_REQUIREMENT')
            ->assertDontSee('PENDING_REQUIREMENT')
            ->assertDontSee('INTERNAL_PM_NOTES')
            ->assertDontSee('888888');
    }

    public function test_approved_requirement_files_can_be_downloaded_and_are_audited(): void
    {
        Storage::fake('local');
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $requirement = Requirement::factory()->create([
            'project_id' => $project->id,
            'client_approval_status' => RequirementApprovalStatus::Approved,
        ]);
        $path = UploadedFile::fake()->create('spec.pdf', 20, 'application/pdf')->store('projects/'.$project->id, 'local');
        $attachment = ProjectAttachment::factory()->create([
            'project_id' => $project->id,
            'requirement_id' => $requirement->id,
            'path' => $path,
            'disk' => 'local',
            'original_name' => 'spec.pdf',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.projects.show', $project))
            ->assertOk()
            ->assertSee('spec.pdf');

        $this->actingAs($user, 'client')
            ->get(route('client.projects.files.download', [$project, $attachment]))
            ->assertOk();

        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'downloaded')->where('client_user_id', $user->id)->exists()
        );
    }

    public function test_client_documents_can_be_downloaded(): void
    {
        Storage::fake('documents');
        $user = ClientUser::factory()->create();
        $document = Document::factory()->approved()->create([
            'client_id' => $user->client_id,
            'title' => 'MSA',
        ]);
        $path = UploadedFile::fake()->create('msa.pdf', 15, 'application/pdf')->store('documents/'.$document->id, 'documents');
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'path' => $path,
            'disk' => 'documents',
            'original_name' => 'msa.pdf',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.documents.index'))
            ->assertOk()
            ->assertSee('MSA');

        $this->actingAs($user, 'client')
            ->get(route('client.documents.download', $document))
            ->assertOk();
    }

    public function test_clients_can_view_accept_and_reject_quotations_with_audit(): void
    {
        $user = ClientUser::factory()->create();
        $quotation = Quotation::factory()->sent()->withItem()->create([
            'client_id' => $user->client_id,
            'title' => 'Website rebuild',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Website rebuild');

        $this->assertSame(QuotationStatus::Viewed, $quotation->fresh()->status);

        $this->actingAs($user, 'client')
            ->get(route('client.quotations.pdf', $quotation))
            ->assertOk();

        $this->actingAs($user, 'client')
            ->post(route('client.quotations.accept', $quotation))
            ->assertRedirect();

        $this->assertSame(QuotationStatus::Accepted, $quotation->fresh()->status);
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'accepted')->where('client_user_id', $user->id)->exists()
        );

        $other = Quotation::factory()->sent()->create(['client_id' => $user->client_id]);
        $this->actingAs($user, 'client')
            ->post(route('client.quotations.reject', $other))
            ->assertRedirect();
        $this->assertSame(QuotationStatus::Rejected, $other->fresh()->status);
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'rejected')->where('client_user_id', $user->id)->exists()
        );
    }

    public function test_invoices_and_payments_are_visible_but_not_mutated(): void
    {
        $user = ClientUser::factory()->create();
        $invoice = Invoice::factory()->sent()->withItem()->create([
            'client_id' => $user->client_id,
            'title' => 'Phase 1',
            'balance' => 6800,
            'amount_paid' => 5000,
        ]);
        $payment = Payment::factory()->create([
            'client_id' => $user->client_id,
            'invoice_id' => $invoice->id,
            'amount' => 5000,
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Phase 1')
            ->assertSee('Outstanding');

        $this->actingAs($user, 'client')
            ->get(route('client.invoices.pdf', $invoice))
            ->assertOk();

        $this->actingAs($user, 'client')
            ->get(route('client.payments.index'))
            ->assertOk()
            ->assertSee($invoice->number);

        $this->actingAs($user, 'client')
            ->get(route('client.payments.pdf', $payment))
            ->assertOk();

        $this->assertSame('5000.00', $payment->fresh()->amount);
        $this->assertSame('6800.00', $invoice->fresh()->balance);
    }

    public function test_clients_can_create_and_reply_to_tickets_without_seeing_internal_notes(): void
    {
        Storage::fake('support');
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $otherProject = Project::factory()->create();

        $this->actingAs($user, 'client')
            ->post(route('client.tickets.store'), [
                'project_id' => $otherProject->id,
                'subject' => 'Should fail',
                'description' => 'Wrong project',
                'priority' => TicketPriority::Normal->value,
            ])
            ->assertSessionHasErrors('project_id');

        $this->actingAs($user, 'client')
            ->post(route('client.tickets.store'), [
                'project_id' => $project->id,
                'subject' => 'Portal login issue',
                'description' => 'Cannot reach staging.',
                'priority' => TicketPriority::High->value,
                'attachment' => UploadedFile::fake()->create('screenshot.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->where('client_id', $user->client_id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame($user->id, $ticket->client_user_id);
        $this->assertNull($ticket->created_by_id);
        $this->assertTrue(str_starts_with($ticket->number, 'TKT-'));
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'created')->where('auditable_id', $ticket->id)->exists()
        );

        TicketMessage::factory()->internal()->create([
            'ticket_id' => $ticket->id,
            'body' => 'SECRET_INTERNAL_NOTE',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Portal login issue')
            ->assertDontSee('SECRET_INTERNAL_NOTE')
            ->assertDontSee('Internal');

        $this->actingAs($user, 'client')
            ->post(route('client.tickets.reply', $ticket), [
                'body' => 'Here is more detail.',
            ])
            ->assertRedirect();

        $this->assertTrue(
            TicketMessage::query()->where('ticket_id', $ticket->id)->where('client_user_id', $user->id)->where('body', 'Here is more detail.')->exists()
        );
        $this->assertFalse(
            TicketMessage::query()->where('ticket_id', $ticket->id)->where('client_user_id', $user->id)->where('is_internal', true)->exists()
        );
        $this->assertTrue(
            AuditLog::query()->where('module', 'client_portal')->where('action', 'replied')->where('client_user_id', $user->id)->exists()
        );

        $ticket->update(['status' => TicketStatus::WaitingForClient]);
        $this->actingAs($user, 'client')
            ->post(route('client.tickets.reply', $ticket), [
                'body' => 'We tested again.',
            ])
            ->assertRedirect();
        $this->assertSame(TicketStatus::InProgress, $ticket->fresh()->status);
    }

    public function test_clients_can_submit_and_view_change_requests(): void
    {
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $other = Project::factory()->create();

        $this->actingAs($user, 'client')
            ->post(route('client.change-requests.store'), [
                'project_id' => $other->id,
                'title' => 'Should fail',
                'description' => 'Not mine',
            ])
            ->assertSessionHasErrors('project_id');

        $this->actingAs($user, 'client')
            ->post(route('client.change-requests.store'), [
                'project_id' => $project->id,
                'title' => 'Add dark mode',
                'description' => 'Please add a theme toggle.',
                'impact_on_timeline_days' => 3,
            ])
            ->assertRedirect();

        $changeRequest = ChangeRequest::query()->where('client_user_id', $user->id)->first();
        $this->assertNotNull($changeRequest);
        $this->assertSame(ChangeRequestStatus::Pending, $changeRequest->status);
        $this->assertNull($changeRequest->impact_on_cost);
        $this->assertNull($changeRequest->requested_by_id);
        $this->assertTrue(str_starts_with($changeRequest->number, 'CR-'));

        $this->actingAs($user, 'client')
            ->get(route('client.change-requests.show', $changeRequest))
            ->assertOk()
            ->assertSee('Add dark mode')
            ->assertDontSee('25000');
    }

    public function test_profile_can_update_name_and_password_but_not_client_master_data(): void
    {
        $user = ClientUser::factory()->create([
            'name' => 'Old Name',
            'password' => 'password',
        ]);
        $clientName = $user->client->name;

        $this->actingAs($user, 'client')
            ->put(route('client.profile.update'), ['name' => 'New Name'])
            ->assertRedirect();

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame($clientName, $user->client->fresh()->name);

        $this->actingAs($user, 'client')
            ->put(route('client.profile.password'), [
                'current_password' => 'password',
                'password' => 'updated-pass',
                'password_confirmation' => 'updated-pass',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('updated-pass', $user->fresh()->password));
    }

    public function test_staff_can_manage_portal_users_with_permission(): void
    {
        Notification::fake();
        $client = Client::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Portal users');

        Livewire::actingAs($admin)
            ->test(Index::class, ['clientId' => $client->id])
            ->set('form.name', 'Portal Owner')
            ->set('form.email', 'owner@client.test')
            ->set('form.password', 'password12')
            ->call('save')
            ->assertHasNoErrors();

        $portalUser = ClientUser::query()->where('email', 'owner@client.test')->first();
        $this->assertNotNull($portalUser);
        $this->assertSame($client->id, $portalUser->client_id);

        Livewire::actingAs($admin)
            ->test(Index::class, ['clientId' => $client->id])
            ->call('sendReset', $portalUser->id);

        Notification::assertSentTo($portalUser, ResetClientPassword::class);

        $viewer = $this->userWithPermissions(['clients.view']);
        Livewire::actingAs($viewer)
            ->test(Index::class, ['clientId' => $client->id])
            ->assertForbidden();
    }

    public function test_sales_manager_seed_includes_portal_permission(): void
    {
        $this->seed();
        $role = Role::query()->where('slug', 'sales-manager')->first();
        $this->assertTrue($role->permissions()->where('name', 'clients.manage_portal')->exists());
        $this->assertFalse($role->permissions()->where('name', 'payroll.manage')->exists());
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Limited',
            'slug' => 'limited-'.uniqid(),
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
