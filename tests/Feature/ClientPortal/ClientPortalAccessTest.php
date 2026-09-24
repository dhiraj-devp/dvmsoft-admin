<?php

namespace Tests\Feature\ClientPortal;

use App\Enums\ClientStatus;
use App\Enums\DocumentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\RequirementApprovalStatus;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\Quotation;
use App\Models\Requirement;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\ResetClientPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_client_login_not_staff_login(): void
    {
        $this->get(route('client.dashboard'))->assertRedirect(route('client.login'));
        $this->get(route('client.projects.index'))->assertRedirect(route('client.login'));
        $this->get(route('client.invoices.index'))->assertRedirect(route('client.login'));
        $this->get(route('client.tickets.index'))->assertRedirect(route('client.login'));
    }

    public function test_staff_users_cannot_open_the_client_portal_with_employee_auth(): void
    {
        $staff = User::factory()->superAdmin()->create();

        $this->actingAs($staff)
            ->get(route('client.dashboard'))
            ->assertRedirect(route('client.login'));
    }

    public function test_client_users_cannot_open_staff_routes(): void
    {
        $user = ClientUser::factory()->create();

        $this->actingAs($user, 'client')
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->actingAs($user, 'client')
            ->get('/users')
            ->assertRedirect(route('login'));
    }

    public function test_employee_credentials_cannot_sign_in_to_the_client_portal(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@dvmsoft.local',
            'password' => 'password',
        ]);

        $this->post(route('client.login.store'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('client');
    }

    public function test_client_credentials_cannot_sign_in_to_admin_os(): void
    {
        $user = ClientUser::factory()->create([
            'email' => 'portal@client.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_inactive_portal_users_cannot_authenticate(): void
    {
        $user = ClientUser::factory()->inactive()->create([
            'password' => 'password',
        ]);

        $this->post(route('client.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('client');
    }

    public function test_inactive_client_accounts_cannot_authenticate(): void
    {
        $client = Client::factory()->create(['status' => ClientStatus::Inactive]);
        $user = ClientUser::factory()->create([
            'client_id' => $client->id,
            'password' => 'password',
        ]);

        $this->post(route('client.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('client');
    }

    public function test_unverified_portal_users_are_prompted_to_verify_email(): void
    {
        $user = ClientUser::factory()->unverified()->create();

        $this->actingAs($user, 'client')
            ->get(route('client.dashboard'))
            ->assertRedirect(route('client.verification.notice'));
    }

    public function test_portal_users_cannot_view_another_clients_records(): void
    {
        $user = ClientUser::factory()->create();
        $other = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $other->id, 'name' => 'SECRET_OTHER_PROJECT']);
        $quotation = Quotation::factory()->sent()->create(['client_id' => $other->id, 'title' => 'SECRET_OTHER_QUOTE']);
        $invoice = Invoice::factory()->sent()->create(['client_id' => $other->id, 'title' => 'SECRET_OTHER_INVOICE']);
        $ticket = Ticket::factory()->create(['client_id' => $other->id, 'subject' => 'SECRET_OTHER_TICKET']);
        $document = Document::factory()->approved()->create(['client_id' => $other->id, 'title' => 'SECRET_OTHER_DOC']);
        $changeRequest = ChangeRequest::factory()->create([
            'project_id' => $project->id,
            'title' => 'SECRET_OTHER_CR',
        ]);

        $this->actingAs($user, 'client')->get(route('client.projects.show', $project))->assertNotFound();
        $this->actingAs($user, 'client')->get(route('client.quotations.show', $quotation))->assertNotFound();
        $this->actingAs($user, 'client')->get(route('client.invoices.show', $invoice))->assertNotFound();
        $this->actingAs($user, 'client')->get(route('client.tickets.show', $ticket))->assertNotFound();
        $this->actingAs($user, 'client')->get(route('client.documents.download', $document))->assertNotFound();
        $this->actingAs($user, 'client')->get(route('client.change-requests.show', $changeRequest))->assertNotFound();

        $this->actingAs($user, 'client')->get(route('client.projects.index'))->assertOk()->assertDontSee('SECRET_OTHER_PROJECT');
        $this->actingAs($user, 'client')->get(route('client.quotations.index'))->assertOk()->assertDontSee('SECRET_OTHER_QUOTE');
        $this->actingAs($user, 'client')->get(route('client.invoices.index'))->assertOk()->assertDontSee('SECRET_OTHER_INVOICE');
        $this->actingAs($user, 'client')->get(route('client.tickets.index'))->assertOk()->assertDontSee('SECRET_OTHER_TICKET');
        $this->actingAs($user, 'client')->get(route('client.documents.index'))->assertOk()->assertDontSee('SECRET_OTHER_DOC');
        $this->actingAs($user, 'client')->get(route('client.change-requests.index'))->assertOk()->assertDontSee('SECRET_OTHER_CR');
    }

    public function test_draft_quotations_and_invoices_are_hidden(): void
    {
        $user = ClientUser::factory()->create();
        Quotation::factory()->draft()->create([
            'client_id' => $user->client_id,
            'title' => 'DRAFT_QUOTE_HIDDEN',
        ]);
        Invoice::factory()->create([
            'client_id' => $user->client_id,
            'status' => InvoiceStatus::Draft,
            'title' => 'DRAFT_INVOICE_HIDDEN',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.quotations.index'))
            ->assertOk()
            ->assertDontSee('DRAFT_QUOTE_HIDDEN');

        $this->actingAs($user, 'client')
            ->get(route('client.invoices.index'))
            ->assertOk()
            ->assertDontSee('DRAFT_INVOICE_HIDDEN');
    }

    public function test_unrelated_company_and_hr_documents_are_not_listed(): void
    {
        $user = ClientUser::factory()->create();
        Document::factory()->approved()->create(['title' => 'HR_POLICY_DOC']);
        Document::factory()->create([
            'client_id' => $user->client_id,
            'status' => DocumentStatus::Draft,
            'title' => 'DRAFT_CLIENT_DOC',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.documents.index'))
            ->assertOk()
            ->assertDontSee('HR_POLICY_DOC')
            ->assertDontSee('DRAFT_CLIENT_DOC');
    }

    public function test_project_files_on_unapproved_requirements_are_forbidden(): void
    {
        Storage::fake('local');
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $requirement = Requirement::factory()->create([
            'project_id' => $project->id,
            'client_approval_status' => RequirementApprovalStatus::Pending,
        ]);
        $path = UploadedFile::fake()->create('internal.pdf', 10, 'application/pdf')->store('projects/'.$project->id, 'local');
        $attachment = ProjectAttachment::factory()->create([
            'project_id' => $project->id,
            'requirement_id' => $requirement->id,
            'path' => $path,
            'disk' => 'local',
            'original_name' => 'internal.pdf',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.projects.files.download', [$project, $attachment]))
            ->assertNotFound();
    }

    public function test_internal_ticket_attachments_cannot_be_downloaded(): void
    {
        Storage::fake('support');
        $user = ClientUser::factory()->create();
        $ticket = Ticket::factory()->create(['client_id' => $user->client_id]);
        $message = TicketMessage::factory()->internal()->create([
            'ticket_id' => $ticket->id,
            'body' => 'SECRET_INTERNAL_NOTE',
        ]);
        $path = UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')->store('tickets/'.$ticket->id, 'support');
        $attachment = TicketAttachment::query()->create([
            'ticket_message_id' => $message->id,
            'original_name' => 'note.pdf',
            'path' => $path,
            'disk' => 'support',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('SECRET_INTERNAL_NOTE');

        $this->actingAs($user, 'client')
            ->get(route('client.tickets.attachments.download', [$ticket, $message, $attachment]))
            ->assertNotFound();
    }

    public function test_password_reset_flow_sends_notification_and_updates_password(): void
    {
        Notification::fake();
        $user = ClientUser::factory()->create(['email' => 'reset@client.test']);

        $this->post(route('client.password.email'), ['email' => $user->email])
            ->assertRedirect();

        $token = null;
        Notification::assertSentTo($user, ResetClientPassword::class, function (ResetClientPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->get(route('client.password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk();

        $this->post(route('client.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('client.login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_email_verification_link_marks_the_user_verified(): void
    {
        $user = ClientUser::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('client.verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user, 'client')->get($url)->assertRedirect(route('client.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
