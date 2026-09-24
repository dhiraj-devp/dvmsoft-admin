<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Livewire\Documents\Index;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\DocumentEventNotification;
use App\Services\DocumentWorkflowService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_use_sequential_numbers_and_existing_relationship_ids(): void
    {
        Storage::fake('documents');
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $employee = Employee::factory()->create();
        $quotation = Quotation::factory()->create(['client_id' => $client->id]);
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'project_id' => $project->id]);
        $type = DocumentType::query()->where('slug', 'contract')->first();

        $clientsBefore = Client::query()->count();
        $projectsBefore = Project::query()->count();
        $employeesBefore = Employee::query()->count();

        $document = app(DocumentWorkflowService::class)->create([
            'title' => 'Master service agreement',
            'document_type_id' => $type->id,
            'description' => 'Company MSA',
            'owner_id' => $admin->id,
            'expiry_date' => now()->addYear()->toDateString(),
            'notes' => '',
            'client_id' => $client->id,
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'quotation_id' => $quotation->id,
            'invoice_id' => $invoice->id,
        ], $admin, UploadedFile::fake()->create('msa.pdf', 20, 'application/pdf'));

        $this->assertSame('DOC-'.now()->year.'-0001', $document->number);
        $this->assertSame($client->id, $document->client_id);
        $this->assertSame($project->id, $document->project_id);
        $this->assertSame($employee->id, $document->employee_id);
        $this->assertSame($quotation->id, $document->quotation_id);
        $this->assertSame($invoice->id, $document->invoice_id);
        $this->assertSame($clientsBefore, Client::query()->count());
        $this->assertSame($projectsBefore, Project::query()->count());
        $this->assertSame($employeesBefore, Employee::query()->count());
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'created')->exists());
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'uploaded')->exists());
    }

    public function test_document_prefix_setting_changes_numbering(): void
    {
        Storage::fake('documents');
        $this->seed();
        settings()->set('documents.prefix', 'CMP');
        $admin = User::factory()->superAdmin()->create();
        $type = DocumentType::query()->where('slug', 'policy')->first();

        $document = app(DocumentWorkflowService::class)->create([
            'title' => 'Leave policy',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $admin->id,
            'expiry_date' => '',
            'notes' => '',
            'client_id' => '',
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('policy.pdf', 10, 'application/pdf'));

        $this->assertTrue(str_starts_with($document->number, 'CMP-'.now()->year.'-'));
    }

    public function test_private_storage_and_authorized_downloads(): void
    {
        Storage::fake('documents');
        Storage::fake('public');
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $type = DocumentType::query()->first();

        $document = app(DocumentWorkflowService::class)->create([
            'title' => 'NDA',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $admin->id,
            'expiry_date' => '',
            'notes' => '',
            'client_id' => '',
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('nda.pdf', 12, 'application/pdf'));

        $version = $document->currentVersion();
        $this->assertSame('documents', $version->disk);
        $this->assertFalse((bool) config('filesystems.disks.documents.serve'));
        $this->assertSame('private', config('filesystems.disks.documents.visibility'));
        $this->assertTrue(Storage::disk('documents')->exists($version->path));
        $this->assertFalse(Storage::disk('public')->exists($version->path));

        $this->get(route('documents.versions.download', [$document, $version]))->assertRedirect(route('login'));

        $viewer = $this->userWithPermissions(['documents.view']);
        $this->actingAs($viewer)
            ->get(route('documents.versions.download', [$document, $version]))
            ->assertForbidden();

        $downloader = $this->userWithPermissions(['documents.download']);
        $this->actingAs($downloader)
            ->get(route('documents.versions.download', [$document, $version]))
            ->assertOk();

        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'downloaded')->exists());
    }

    public function test_new_versions_keep_previous_files(): void
    {
        Storage::fake('documents');
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $type = DocumentType::query()->first();
        $workflow = app(DocumentWorkflowService::class);

        $document = $workflow->create([
            'title' => 'Contract',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $admin->id,
            'expiry_date' => '',
            'notes' => '',
            'client_id' => '',
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('v1.pdf', 8, 'application/pdf'));

        $first = $document->currentVersion();
        $this->assertSame(1, $first->version_number);
        $this->assertSame('v1', $first->version_label);

        $second = $workflow->uploadVersion($document->fresh(), $admin, UploadedFile::fake()->create('v2.pdf', 9, 'application/pdf'), 'Clause 4 updated');

        $this->assertSame(2, $second->version_number);
        $this->assertSame('v2', $second->version_label);
        $this->assertTrue(Storage::disk('documents')->exists($first->path));
        $this->assertTrue(Storage::disk('documents')->exists($second->path));
        $this->assertNotSame($first->path, $second->path);
        $this->assertSame(2, $document->fresh()->current_version_number);
        $this->assertSame(2, DocumentVersion::query()->where('document_id', $document->id)->count());
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'version_created')->exists());

        $this->actingAs($admin)
            ->get(route('documents.versions.download', [$document, $first]))
            ->assertOk();
    }

    public function test_approval_workflow_notifications_and_audit(): void
    {
        Notification::fake();
        Storage::fake('documents');
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->superAdmin()->create();
        $type = DocumentType::query()->first();
        $workflow = app(DocumentWorkflowService::class);

        $document = $workflow->create([
            'title' => 'Vendor agreement',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $owner->id,
            'expiry_date' => '',
            'notes' => '',
            'client_id' => '',
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('vendor.pdf', 11, 'application/pdf'));

        $this->assertSame(DocumentStatus::Draft, $document->status);

        $workflow->submit($document->fresh(), $admin);
        $this->assertSame(DocumentStatus::Review, $document->fresh()->status);
        Notification::assertSentTo($owner, DocumentEventNotification::class, function (DocumentEventNotification $notification) {
            return $notification->event === 'submitted';
        });
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'submitted')->exists());

        $workflow->reject($document->fresh(), $owner, 'Missing annexure');
        $this->assertSame(DocumentStatus::Draft, $document->fresh()->status);
        $this->assertSame('Missing annexure', $document->fresh()->approval_notes);
        Notification::assertSentTo($admin, DocumentEventNotification::class, function (DocumentEventNotification $notification) {
            return $notification->event === 'rejected';
        });
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'rejected')->exists());

        $workflow->submit($document->fresh(), $admin);
        $workflow->approve($document->fresh(), $owner, 'Looks good');
        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);
        $this->assertSame($owner->id, $document->fresh()->approved_by_id);
        $this->assertNotNull($document->fresh()->approved_at);
        Notification::assertSentTo($admin, DocumentEventNotification::class, function (DocumentEventNotification $notification) {
            return $notification->event === 'approved';
        });
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'approved')->exists());

        $workflow->markSent($document->fresh(), $admin);
        $this->assertSame(DocumentStatus::Sent, $document->fresh()->status);
        $workflow->markSigned($document->fresh(), $admin);
        $this->assertSame(DocumentStatus::Signed, $document->fresh()->status);
        $workflow->archive($document->fresh(), $admin);
        $this->assertSame(DocumentStatus::Archived, $document->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'archived')->exists());
    }

    public function test_search_and_filters_find_related_records(): void
    {
        Storage::fake('documents');
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create(['name' => 'Acme Holdings']);
        $other = Client::factory()->create(['name' => 'Other Co']);
        $type = DocumentType::query()->where('slug', 'legal')->first();
        $workflow = app(DocumentWorkflowService::class);

        $keep = $workflow->create([
            'title' => 'Acme NDA pack',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $admin->id,
            'expiry_date' => now()->addDays(10)->toDateString(),
            'notes' => '',
            'client_id' => $client->id,
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('acme.pdf', 6, 'application/pdf'));

        $workflow->create([
            'title' => 'Other memo',
            'document_type_id' => $type->id,
            'description' => '',
            'owner_id' => $admin->id,
            'expiry_date' => '',
            'notes' => '',
            'client_id' => $other->id,
            'project_id' => '',
            'employee_id' => '',
            'quotation_id' => '',
            'invoice_id' => '',
        ], $admin, UploadedFile::fake()->create('other.pdf', 6, 'application/pdf'));

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('search', 'Acme')
            ->assertSee('Acme NDA pack')
            ->assertDontSee('Other memo');

        Livewire::test(Index::class)
            ->set('clientId', $client->id)
            ->assertSee($keep->number)
            ->assertDontSee('Other memo');

        Livewire::test(Index::class)
            ->set('expiry', 'expiring')
            ->assertSee('Acme NDA pack')
            ->assertDontSee('Other memo');
    }

    public function test_expiring_command_notifies_owners(): void
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

    public function test_deleting_a_document_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $document = Document::factory()->create();

        $this->actingAs($admin);
        $document->delete();

        $this->assertTrue(AuditLog::query()->where('module', 'documents')->where('action', 'deleted')->exists());
        $this->assertSoftDeleted($document);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Limited Documents',
            'slug' => 'limited-documents-'.uniqid(),
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
