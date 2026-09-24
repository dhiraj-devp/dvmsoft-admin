<?php

namespace Tests\Feature\Projects;

use App\Livewire\ProjectDelivery\Workspace;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StagePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_without_stage_permissions_cannot_manage_delivery(): void
    {
        $user = $this->userWithPermissions(['projects.view']);
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project, 'tab' => 'delivery']))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['projectId' => $project->id])
            ->call('createStage')
            ->assertForbidden();
    }

    public function test_submit_review_permission_is_separate_from_manage(): void
    {
        $user = $this->userWithPermissions([
            'projects.view',
            'projects.stages.view',
            'projects.stages.submit_review',
        ]);
        $stage = ProjectStage::factory()->inProgress()->create();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['projectId' => $stage->project_id, 'stageId' => $stage->id])
            ->call('submitStage')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(Workspace::class, ['projectId' => $stage->project_id, 'stageId' => $stage->id])
            ->call('createStage')
            ->assertForbidden();
    }

    public function test_ordinary_employees_do_not_receive_stage_permissions_automatically(): void
    {
        $this->seed();
        $employee = User::factory()->create();
        $role = Role::query()->where('slug', 'employee')->first();
        $employee->roles()->attach($role);

        $this->assertFalse($employee->fresh()->hasPermission('projects.stages.manage'));
        $this->assertFalse($employee->fresh()->hasPermission('projects.stage_approval.override'));
    }
}
