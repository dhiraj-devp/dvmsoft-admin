<?php

namespace Tests\Feature\Projects;

use App\Enums\ChangeRequestStatus;
use App\Livewire\Projects\Form;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Models\AuditLog;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ChangeRequestWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_can_be_created_through_the_form_and_is_audited(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $client = Client::factory()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('form.client_id', $client->id)
            ->set('form.name', 'Portal rebuild')
            ->set('form.manager_id', $admin->id)
            ->set('form.budget', '180000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Portal rebuild',
            'client_id' => $client->id,
        ]);
        $this->assertTrue(
            AuditLog::query()->where('module', 'projects')->where('action', 'created')->exists()
        );
    }

    public function test_change_requests_move_pending_to_approved_to_implemented(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $changeRequest = ChangeRequest::factory()->pending()->create();
        $workflow = app(ChangeRequestWorkflowService::class);

        $workflow->approve($changeRequest, $admin);
        $this->assertSame(ChangeRequestStatus::Approved, $changeRequest->fresh()->status);

        $workflow->implement($changeRequest->fresh(), $admin);
        $this->assertSame(ChangeRequestStatus::Implemented, $changeRequest->fresh()->status);
        $this->assertTrue(
            AuditLog::query()->where('module', 'change_requests')->where('action', 'updated')->exists()
        );
    }

    public function test_my_tasks_page_only_shows_tasks_assigned_to_the_current_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->create();
        $mine = Task::factory()->create(['assigned_user_id' => $admin->id, 'title' => 'Mine only']);
        Task::factory()->create(['assigned_user_id' => $other->id, 'title' => 'Someone else']);

        Livewire::actingAs($admin)
            ->test(TasksIndex::class, ['mineOnly' => true])
            ->assertSee('Mine only')
            ->assertDontSee('Someone else');

        $this->assertSame($admin->id, $mine->assigned_user_id);
    }
}
