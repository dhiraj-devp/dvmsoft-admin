<?php

namespace Tests\Feature\ClientPortal;

use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Livewire\Portal\ProjectDelivery;
use App\Models\ClientUser;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStageEvidence;
use App\Models\ProjectStageMessage;
use App\Models\User;
use App\Services\ProjectStageWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_project_page_shows_stage_timeline_without_internal_data(): void
    {
        $user = ClientUser::factory()->create();
        $manager = User::factory()->create(['name' => 'SECRET_STAGE_OWNER']);
        $project = Project::factory()->active()->create([
            'client_id' => $user->client_id,
            'manager_id' => $manager->id,
            'budget' => 888888,
            'notes' => 'SECRET_PROJECT_NOTES',
        ]);
        ProjectStage::factory()->create([
            'project_id' => $project->id,
            'name' => 'Design',
            'status' => StageStatus::InProgress,
            'notes' => 'SECRET_STAGE_NOTES',
            'owner_id' => $manager->id,
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.projects.show', $project))
            ->assertOk()
            ->assertSee('Delivery timeline')
            ->assertSee('Design')
            ->assertDontSee('SECRET_STAGE_NOTES')
            ->assertDontSee('SECRET_PROJECT_NOTES')
            ->assertDontSee('888888')
            ->assertDontSee('SECRET_STAGE_OWNER');
    }

    public function test_client_cannot_see_another_clients_stage_or_evidence(): void
    {
        Storage::fake('stages');
        $user = ClientUser::factory()->create();
        $other = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $other->client_id]);
        $stage = ProjectStage::factory()->readyForReview()->create(['project_id' => $project->id]);
        $evidence = ProjectStageEvidence::factory()->create([
            'stage_id' => $stage->id,
            'path' => 'hidden.png',
            'disk' => 'stages',
            'original_name' => 'hidden.png',
            'visibility' => StageEvidenceVisibility::Client,
        ]);
        Storage::disk('stages')->put('hidden.png', 'secret');

        $this->actingAs($user, 'client')
            ->get(route('client.projects.show', $project))
            ->assertNotFound();

        $this->actingAs($user, 'client')
            ->get(route('client.projects.stages.evidence.download', [$project, $stage, $evidence]))
            ->assertNotFound();
    }

    public function test_internal_evidence_and_discussion_are_hidden_from_the_portal(): void
    {
        Storage::fake('stages');
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $stage = ProjectStage::factory()->inProgress()->create(['project_id' => $project->id]);
        $internal = ProjectStageEvidence::factory()->internal()->create([
            'stage_id' => $stage->id,
            'title' => 'INTERNAL_EVIDENCE',
            'path' => 'internal.png',
            'disk' => 'stages',
            'original_name' => 'internal.png',
        ]);
        Storage::disk('stages')->put('internal.png', 'nope');
        ProjectStageEvidence::factory()->create([
            'stage_id' => $stage->id,
            'title' => 'Client preview',
            'visibility' => StageEvidenceVisibility::Client,
        ]);
        ProjectStageMessage::factory()->internal()->create([
            'stage_id' => $stage->id,
            'body' => 'INTERNAL_CHAT',
        ]);
        ProjectStageMessage::factory()->create([
            'stage_id' => $stage->id,
            'body' => 'Visible comment',
            'is_internal' => false,
        ]);

        Livewire::actingAs($user, 'client')
            ->test(ProjectDelivery::class, ['projectId' => $project->id, 'stageId' => $stage->id])
            ->call('setSection', 'evidence')
            ->assertSee('Client preview')
            ->assertDontSee('INTERNAL_EVIDENCE')
            ->call('setSection', 'discussion')
            ->assertSee('Visible comment')
            ->assertDontSee('INTERNAL_CHAT');

        $this->actingAs($user, 'client')
            ->get(route('client.projects.stages.evidence.download', [$project, $stage, $internal]))
            ->assertNotFound();
    }

    public function test_client_can_approve_and_request_changes_from_the_portal(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $user = ClientUser::factory()->create();
        $project = Project::factory()->create(['client_id' => $user->client_id]);
        $stage = ProjectStage::factory()->inProgress()->create(['project_id' => $project->id, 'name' => 'QA']);
        app(ProjectStageWorkflowService::class)->submitForReview($stage, $admin, 'Please review');

        Livewire::actingAs($user, 'client')
            ->test(ProjectDelivery::class, ['projectId' => $project->id, 'stageId' => $stage->id])
            ->set('changeReason', '')
            ->call('requestChanges')
            ->assertHasErrors(['changeReason']);

        Livewire::actingAs($user, 'client')
            ->test(ProjectDelivery::class, ['projectId' => $project->id, 'stageId' => $stage->id])
            ->set('changeReason', 'Contrast is too low')
            ->call('requestChanges')
            ->assertHasNoErrors();

        $this->assertSame(StageStatus::ChangesRequested, $stage->fresh()->status);

        app(ProjectStageWorkflowService::class)->resume($stage->fresh(), $admin);
        app(ProjectStageWorkflowService::class)->submitForReview($stage->fresh(), $admin);

        Livewire::actingAs($user, 'client')
            ->test(ProjectDelivery::class, ['projectId' => $project->id, 'stageId' => $stage->id])
            ->set('reviewComment', 'Looks great')
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertSame(StageStatus::Approved, $stage->fresh()->status);
        $this->assertSame(2, $stage->submissions()->count());
    }

    public function test_client_dashboard_surfaces_stages_awaiting_review(): void
    {
        $user = ClientUser::factory()->create();
        $project = Project::factory()->active()->create(['client_id' => $user->client_id, 'name' => 'Portal Rebuild']);
        ProjectStage::factory()->readyForReview()->create([
            'project_id' => $project->id,
            'name' => 'Design review',
            'client_review_enabled' => true,
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee('Needs your review')
            ->assertSee('Design review')
            ->assertSee('Stages to review');
    }

    public function test_existing_client_portal_project_page_still_shows_milestones_and_hides_budget(): void
    {
        $user = ClientUser::factory()->create();
        $project = Project::factory()->active()->create([
            'client_id' => $user->client_id,
            'budget' => 424242,
            'notes' => 'KEEP_HIDDEN',
            'name' => 'Visible Portal Project',
        ]);

        $this->actingAs($user, 'client')
            ->get(route('client.projects.show', $project))
            ->assertOk()
            ->assertSee('Visible Portal Project')
            ->assertDontSee('KEEP_HIDDEN')
            ->assertDontSee('424242');
    }
}
