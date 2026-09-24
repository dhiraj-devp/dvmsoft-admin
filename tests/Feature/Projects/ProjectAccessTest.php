<?php

namespace Tests\Feature\Projects;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_project_routes(): void
    {
        $this->get(route('projects.dashboard'))->assertRedirect(route('login'));
        $this->get(route('projects.index'))->assertRedirect(route('login'));
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
        $this->get(route('milestones.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_project_permissions_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('projects.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('projects.index'))->assertForbidden();
        $this->actingAs($user)->get(route('tasks.index'))->assertForbidden();
        $this->actingAs($user)->get(route('milestones.index'))->assertForbidden();
    }

    public function test_super_admin_can_open_project_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $project = Project::factory()->create();

        $this->actingAs($admin)->get(route('projects.dashboard'))->assertOk()->assertSee('Total projects');
        $this->actingAs($admin)->get(route('projects.index'))->assertOk();
        $this->actingAs($admin)->get(route('projects.create'))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', ['project' => $project, 'tab' => 'tasks']))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', ['project' => $project, 'tab' => 'delivery']))->assertOk();
        $this->actingAs($admin)->get(route('projects.edit', $project))->assertOk();
        $this->actingAs($admin)->get(route('tasks.index'))->assertOk();
        $this->actingAs($admin)->get(route('milestones.index'))->assertOk();
    }

    public function test_granular_task_permission_does_not_open_projects(): void
    {
        $role = Role::query()->create(['name' => 'Task Viewer', 'slug' => 'task-viewer-'.uniqid()]);
        $permission = Permission::query()->create([
            'name' => 'tasks.view',
            'group' => 'tasks',
            'description' => 'View tasks',
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->get(route('tasks.index'))->assertOk();
        $this->actingAs($user)->get(route('projects.index'))->assertForbidden();
    }
}
