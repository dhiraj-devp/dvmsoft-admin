<?php

namespace Tests\Feature\Projects;

use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ProjectFromQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationToProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_converted_quotation_creates_a_project_without_duplicating_the_client(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create(['name' => 'Northwind Labs']);
        $quotation = Quotation::factory()->converted()->withItem()->create([
            'client_id' => $client->id,
            'title' => 'Commerce rebuild',
            'total' => 250000,
        ]);

        $this->actingAs($admin)
            ->post(route('quotations.project', $quotation))
            ->assertRedirect();

        $project = Project::query()->first();

        $this->assertNotNull($project);
        $this->assertSame($client->id, $project->client_id);
        $this->assertSame($quotation->id, $project->quotation_id);
        $this->assertSame('Commerce rebuild', $project->name);
        $this->assertSame('250000.00', (string) $project->budget);
        $this->assertSame(ProjectStatus::Planning, $project->status);
        $this->assertTrue(str_starts_with($project->number, 'PRJ-'.now()->year.'-'));
        $this->assertSame(1, Client::query()->count());
        $this->assertTrue(
            AuditLog::query()->where('module', 'projects')->where('action', 'created')->exists()
        );
    }

    public function test_creating_a_project_twice_from_the_same_quotation_reuses_the_record(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $quotation = Quotation::factory()->converted()->create();

        $first = app(ProjectFromQuotationService::class)->create($quotation, $admin);
        $second = app(ProjectFromQuotationService::class)->create($quotation->fresh(), $admin);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Project::query()->count());
    }

    public function test_an_unconverted_quotation_cannot_become_a_project(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $quotation = Quotation::factory()->create(['status' => QuotationStatus::Accepted]);

        $this->actingAs($admin)
            ->from(route('quotations.show', $quotation))
            ->post(route('quotations.project', $quotation))
            ->assertForbidden();

        $this->assertSame(0, Project::query()->count());
    }

    public function test_users_without_project_create_permission_cannot_create_from_quotation(): void
    {
        $user = User::factory()->create();
        $quotation = Quotation::factory()->converted()->create();

        $this->actingAs($user)
            ->post(route('quotations.project', $quotation))
            ->assertForbidden();
    }
}
