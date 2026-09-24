<?php

namespace Tests\Feature\Projects;

use App\Enums\ClientApprovalMode;
use App\Enums\StageApprovalRequirement;
use App\Enums\StageEvidenceType;
use App\Enums\StageEvidenceVisibility;
use App\Enums\StageStatus;
use App\Enums\StageSubmissionStatus;
use App\Enums\TaskStatus;
use App\Livewire\ProjectDelivery\Workspace;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Models\AuditLog;
use App\Models\ClientUser;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\ProjectStageDeliverable;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ClientPortalEventNotification;
use App\Notifications\OperationalNotification;
use App\Services\ClientPortal\ClientAccess;
use App\Services\ProjectStageWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stages_can_be_created_reordered_and_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();
        $workflow = app(ProjectStageWorkflowService::class);

        $first = $workflow->create($project, ['name' => 'Discovery'], $admin);
        $second = $workflow->create($project, ['name' => 'Design'], $admin);

        $this->assertSame(1, $first->sequence);
        $this->assertSame(2, $second->sequence);

        $workflow->reorder($project, [$second->id, $first->id], $admin);

        $this->assertSame(1, $second->fresh()->sequence);
        $this->assertSame(2, $first->fresh()->sequence);
        $this->assertTrue(AuditLog::query()->where('module', 'project_stages')->where('action', 'created')->exists());
        $this->assertTrue(AuditLog::query()->where('module', 'project_stages')->where('action', 'reordered')->exists());
    }

    public function test_tasks_can_belong_to_a_stage_and_drive_progress(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();
        $stage = ProjectStage::factory()->create(['project_id' => $project->id, 'name' => 'Build']);
        $open = Task::factory()->create(['project_id' => $project->id, 'stage_id' => $stage->id, 'status' => TaskStatus::Todo]);
        Task::factory()->create(['project_id' => $project->id, 'stage_id' => $stage->id, 'status' => TaskStatus::Completed]);

        $this->assertSame(50, $stage->fresh()->progressPercent());

        Livewire::actingAs($admin)
            ->test(TasksIndex::class, ['projectId' => $project->id])
            ->call('edit', $open->id)
            ->set('form.stage_id', $stage->id)
            ->set('form.status', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(100, $stage->fresh()->progressPercent());
        $this->assertSame(100, $project->fresh()->progressPercent());
    }

    public function test_deliverables_and_evidence_are_required_before_review_unless_overridden(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();
        $stage = ProjectStage::factory()->inProgress()->create([
            'project_id' => $project->id,
            'requires_evidence' => true,
        ]);
        ProjectStageDeliverable::factory()->create(['stage_id' => $stage->id, 'is_required' => true]);
        $workflow = app(ProjectStageWorkflowService::class);

        try {
            $workflow->submitForReview($stage, $admin);
            $this->fail('Incomplete stage should not submit.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $workflow->submitForReview($stage, $admin, 'Ship it', true, 'Client demo is tomorrow');

        $this->assertSame(StageStatus::ReadyForReview, $stage->fresh()->status);
        $this->assertTrue($stage->latestSubmission()->override_incomplete);
        $this->assertTrue(AuditLog::query()->where('module', 'project_stages')->where('action', 'override')->exists());
    }

    public function test_external_links_are_stored_as_urls_not_files(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $stage = ProjectStage::factory()->inProgress()->create();
        $evidence = app(ProjectStageWorkflowService::class)->addEvidence($stage, [
            'type' => StageEvidenceType::Url,
            'title' => 'Figma',
            'url' => 'https://www.figma.com/file/abc',
            'visibility' => StageEvidenceVisibility::Client->value,
        ], $admin);

        $this->assertSame('https://www.figma.com/file/abc', $evidence->url);
        $this->assertNull($evidence->path);
        $this->assertFalse($evidence->hasFile());
    }

    public function test_evidence_files_use_private_storage(): void
    {
        Storage::fake('stages');
        $admin = User::factory()->superAdmin()->create();
        $stage = ProjectStage::factory()->inProgress()->create();
        $file = UploadedFile::fake()->image('shot.png');

        $evidence = app(ProjectStageWorkflowService::class)->addEvidence($stage, [
            'type' => StageEvidenceType::Screenshot,
            'title' => 'Homepage',
            'visibility' => StageEvidenceVisibility::Client->value,
        ], $admin, $file);

        Storage::disk('stages')->assertExists($evidence->path);
        $this->actingAs($admin)
            ->get(route('projects.stages.evidence.download', [$stage->project, $stage, $evidence]))
            ->assertOk();
    }

    public function test_strict_mode_blocks_completion_and_the_next_stage(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create(['client_approval_mode' => ClientApprovalMode::Strict]);
        $first = ProjectStage::factory()->inProgress()->create([
            'project_id' => $project->id,
            'sequence' => 1,
            'approval_requirement' => StageApprovalRequirement::Inherit,
        ]);
        $second = ProjectStage::factory()->create([
            'project_id' => $project->id,
            'sequence' => 2,
            'name' => 'Development',
        ]);
        $workflow = app(ProjectStageWorkflowService::class);

        $workflow->submitForReview($first, $admin);

        try {
            $workflow->complete($first->fresh(), $admin);
            $this->fail('Strict stages must not complete without approval.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        try {
            $workflow->start($second, $admin);
            $this->fail('Next stage must wait for the required previous stage.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('must be approved', $exception->errors()['status'][0]);
        }

        $client = ClientUser::factory()->create(['client_id' => $project->client_id]);
        $workflow->approve($first->fresh(), $client, 'Looks good');
        $workflow->complete($first->fresh(), $admin);
        $workflow->start($second->fresh(), $admin);

        $this->assertSame(StageStatus::Completed, $first->fresh()->status);
        $this->assertSame(StageStatus::InProgress, $second->fresh()->status);
    }

    public function test_flexible_mode_does_not_block_progression(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create(['client_approval_mode' => ClientApprovalMode::Flexible]);
        $first = ProjectStage::factory()->inProgress()->create([
            'project_id' => $project->id,
            'sequence' => 1,
            'approval_requirement' => StageApprovalRequirement::Inherit,
        ]);
        $second = ProjectStage::factory()->create(['project_id' => $project->id, 'sequence' => 2]);
        $workflow = app(ProjectStageWorkflowService::class);

        $workflow->complete($first, $admin);
        $workflow->start($second, $admin);

        $this->assertSame(StageStatus::Completed, $first->fresh()->status);
        $this->assertSame(StageStatus::InProgress, $second->fresh()->status);
    }

    public function test_per_stage_required_override_gates_even_in_flexible_projects(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create(['client_approval_mode' => ClientApprovalMode::Flexible]);
        $stage = ProjectStage::factory()->inProgress()->create([
            'project_id' => $project->id,
            'approval_requirement' => StageApprovalRequirement::Required,
        ]);
        $workflow = app(ProjectStageWorkflowService::class);
        $workflow->submitForReview($stage, $admin);

        try {
            $workflow->complete($stage->fresh(), $admin);
            $this->fail('Explicit required stages must not skip approval.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_client_approval_and_change_requests_keep_review_history(): void
    {
        Notification::fake();
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create(['manager_id' => $admin->id]);
        $client = ClientUser::factory()->create(['client_id' => $project->client_id]);
        $stage = ProjectStage::factory()->inProgress()->create(['project_id' => $project->id]);
        $workflow = app(ProjectStageWorkflowService::class);

        $first = $workflow->submitForReview($stage, $admin, 'First pass');
        $workflow->requestChanges($stage->fresh(), $client, 'Please fix the mobile layout');
        $this->assertSame(StageStatus::ChangesRequested, $stage->fresh()->status);

        $workflow->resume($stage->fresh(), $admin);
        $second = $workflow->submitForReview($stage->fresh(), $admin, 'Second pass');
        $workflow->approve($stage->fresh(), $client, 'Approved');

        $this->assertSame(StageSubmissionStatus::ChangesRequested, $first->fresh()->status);
        $this->assertSame(StageSubmissionStatus::Approved, $second->fresh()->status);
        $this->assertSame(2, $stage->submissions()->count());
        $this->assertSame('Please fix the mobile layout', $first->fresh()->decision_comment);
        Notification::assertSentTo($client, ClientPortalEventNotification::class);
        Notification::assertSentTo($admin, OperationalNotification::class);
    }

    public function test_internal_discussion_never_mixes_with_client_messages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();
        $client = ClientUser::factory()->create(['client_id' => $project->client_id]);
        $stage = ProjectStage::factory()->inProgress()->create(['project_id' => $project->id]);
        $workflow = app(ProjectStageWorkflowService::class);

        $internal = $workflow->postMessage($stage, ['body' => 'SECRET_INTERNAL_NOTE', 'is_internal' => true], $admin);
        $public = $workflow->postMessage($stage, ['body' => 'Client-visible update'], $admin);

        $this->assertTrue($internal->is_internal);
        $this->assertFalse($public->is_internal);
        $this->assertSame(1, $stage->allMessages()->visibleToClient()->count());
        $this->assertFalse(
            app(ClientAccess::class)
                ->stageMessages($client, $project, $stage)
                ->where('body', 'SECRET_INTERNAL_NOTE')
                ->exists()
        );
    }

    public function test_existing_project_pages_still_work_with_a_delivery_tab(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();
        ProjectStage::factory()->create(['project_id' => $project->id, 'name' => 'Discovery']);

        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', ['project' => $project, 'tab' => 'delivery']))
            ->assertOk()
            ->assertSee('Stage timeline')
            ->assertSee('Discovery');

        Livewire::actingAs($admin)
            ->test(Workspace::class, ['projectId' => $project->id])
            ->assertSee('Discovery');
    }

    public function test_projects_without_stages_keep_task_based_progress(): void
    {
        $project = Project::factory()->create();
        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Completed]);
        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo]);

        $this->assertSame(50, $project->progressPercent());
    }
}
