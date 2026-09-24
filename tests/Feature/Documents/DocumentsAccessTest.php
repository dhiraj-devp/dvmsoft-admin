<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_document_routes(): void
    {
        $document = Document::factory()->create();

        $this->get(route('documents.dashboard'))->assertRedirect(route('login'));
        $this->get(route('documents.index'))->assertRedirect(route('login'));
        $this->get(route('documents.create'))->assertRedirect(route('login'));
        $this->get(route('documents.show', $document))->assertRedirect(route('login'));
        $this->get(route('documents.expiring'))->assertRedirect(route('login'));
        $this->get(route('document-types.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_document_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create();

        $this->actingAs($user)->get(route('documents.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('documents.index'))->assertForbidden();
        $this->actingAs($user)->get(route('documents.create'))->assertForbidden();
        $this->actingAs($user)->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs($user)->get(route('documents.expiring'))->assertForbidden();
        $this->actingAs($user)->get(route('document-types.index'))->assertForbidden();
    }

    public function test_super_admin_can_open_document_pages(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $document = Document::factory()->create();

        $this->actingAs($admin)->get(route('documents.dashboard'))->assertOk()->assertSee('Total documents');
        $this->actingAs($admin)->get(route('documents.index'))->assertOk();
        $this->actingAs($admin)->get(route('documents.create'))->assertOk();
        $this->actingAs($admin)->get(route('documents.show', $document))->assertOk();
        $this->actingAs($admin)->get(route('documents.expiring'))->assertOk();
        $this->actingAs($admin)->get(route('document-types.index'))->assertOk();
    }

    public function test_granular_document_permissions_and_role_seed(): void
    {
        $viewer = $this->userWithPermissions(['documents.view']);

        $this->actingAs($viewer)->get(route('documents.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('documents.index'))->assertOk();
        $this->actingAs($viewer)->get(route('documents.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('document-types.index'))->assertForbidden();

        $this->seed();
        $hr = Role::query()->where('slug', 'hr-manager')->first();
        $this->assertTrue($hr->permissions()->where('name', 'documents.view')->exists());
        $this->assertTrue($hr->permissions()->where('name', 'documents.approve')->exists());
        $this->assertFalse($hr->permissions()->where('name', 'documents.manage_types')->exists());
        $this->assertFalse($hr->permissions()->where('name', 'payroll.view')->exists());
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'Document Viewer',
            'slug' => 'document-viewer-'.uniqid(),
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
