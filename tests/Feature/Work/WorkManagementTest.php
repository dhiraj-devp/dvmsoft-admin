<?php

namespace Tests\Feature\Work;

use App\Enums\WorkDailyUpdateStatus;
use App\Enums\WorkManagerStamp;
use App\Enums\WorkPlanItemStatus;
use App\Livewire\Work\MyWork;
use App\Livewire\Work\TeamProgress;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkDailyPlan;
use App\Models\WorkDailyUpdate;
use App\Models\WorkGoal;
use App\Models\WorkReview;
use App\Services\NavigationService;
use App\Services\Work\WorkAttentionService;
use App\Services\Work\WorkProgressScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class WorkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_staff_can_save_simple_daily_progress(): void
    {
        $user = $this->userWithPermissions(['work.my.view', 'work.my.manage']);

        Livewire::actingAs($user)
            ->test(MyWork::class)
            ->assertSee('Analytics')
            ->assertSee('List')
            ->assertSee("Create today's report")
            ->call('openTab', 'analytics')
            ->assertSet('tab', 'analytics')
            ->assertSee('This week')
            ->assertSee('Last 4 weeks')
            ->assertSee('This month')
            ->call('createToday')
            ->assertSet('tab', 'create')
            ->set('accomplished', 'Studied Laravel routes. Called 8 people.')
            ->set('pending', '2 numbers not connected.')
            ->set('learned', 'Route groups')
            ->set('growthNote', 'Faster with Laravel routes')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('tab', 'show')
            ->assertSee('Studied Laravel routes. Called 8 people.')
            ->assertSee('In review')
            ->assertSee('Faster with Laravel routes')
            ->call('openTab', 'analytics')
            ->assertSet('tab', 'analytics')
            ->assertSee('This week')
            ->assertSee('Last 4 weeks')
            ->assertSee('This month')
            ->assertSee('Faster with Laravel routes');

        $this->assertDatabaseHas('work_daily_updates', [
            'user_id' => $user->id,
            'accomplished' => 'Studied Laravel routes. Called 8 people.',
            'pending' => '2 numbers not connected.',
            'learned' => 'Route groups',
            'notes' => 'Faster with Laravel routes',
            'status' => WorkDailyUpdateStatus::InReview->value,
        ]);
    }

    public function test_staff_can_edit_a_report_until_it_is_done(): void
    {
        $user = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $report = WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'accomplished' => 'First draft.',
        ]);

        Livewire::actingAs($user)
            ->test(MyWork::class)
            ->call('edit', $report->id)
            ->set('accomplished', 'Updated the landing page.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Updated the landing page.');

        $done = WorkDailyUpdate::factory()->done()->create([
            'user_id' => $user->id,
            'work_date' => now()->subDay()->toDateString(),
            'accomplished' => 'Already reviewed.',
        ]);

        Livewire::actingAs($user)
            ->test(MyWork::class)
            ->call('edit', $done->id)
            ->assertForbidden();
    }

    public function test_staff_cannot_open_team_progress_but_their_manager_can(): void
    {
        $manager = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern->update(['manager_id' => $manager->id]);

        WorkDailyUpdate::factory()->create([
            'user_id' => $intern->id,
            'work_date' => now()->toDateString(),
            'accomplished' => 'Finished Git practice.',
        ]);

        $this->actingAs($intern)->get(route('work.team'))->assertForbidden();
        $this->actingAs($manager)->get(route('work.team'))->assertOk()->assertSee('Finished Git practice.');

        $update = WorkDailyUpdate::query()->where('user_id', $intern->id)->first();

        Livewire::actingAs($manager)
            ->test(TeamProgress::class)
            ->call('read', $update->id)
            ->assertSet('readingId', $update->id)
            ->call('markDone', $update->id, 'good')
            ->assertHasNoErrors();

        $this->assertSame(WorkDailyUpdateStatus::Done, $update->fresh()->status);
        $this->assertSame(WorkManagerStamp::Good, $update->fresh()->manager_stamp);
    }

    public function test_manager_can_mark_multiple_reports_done_in_one_click(): void
    {
        $manager = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern->update(['manager_id' => $manager->id]);

        $first = WorkDailyUpdate::factory()->create([
            'user_id' => $intern->id,
            'work_date' => now()->toDateString(),
            'accomplished' => 'Morning calls.',
        ]);
        $second = WorkDailyUpdate::factory()->create([
            'user_id' => $intern->id,
            'work_date' => now()->subDay()->toDateString(),
            'accomplished' => 'Afternoon learning.',
        ]);

        Livewire::actingAs($manager)
            ->test(TeamProgress::class)
            ->set('selected', [$first->id, $second->id])
            ->call('markSelectedDone')
            ->assertHasNoErrors();

        $this->assertSame(WorkDailyUpdateStatus::Done, $first->fresh()->status);
        $this->assertSame(WorkDailyUpdateStatus::Done, $second->fresh()->status);
    }

    public function test_progress_score_is_withheld_until_enough_updates_exist(): void
    {
        $user = User::factory()->create();
        WorkGoal::factory()->create(['assigned_user_id' => $user->id, 'created_by' => $user->id]);

        $result = app(WorkProgressScore::class)->forUser($user);

        $this->assertFalse($result->enoughData);
        $this->assertNull($result->percent);
        $this->assertSame('Not enough data', $result->label());
    }

    public function test_progress_score_uses_the_configured_signals_when_data_exists(): void
    {
        $user = User::factory()->create();
        WorkGoal::factory()->completed()->create([
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        foreach (range(0, 4) as $offset) {
            $date = now()->subDays($offset)->toDateString();
            WorkDailyPlan::factory()->withItems(2, WorkPlanItemStatus::Completed)->create([
                'user_id' => $user->id,
                'work_date' => $date,
            ]);
            WorkDailyUpdate::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date,
            ]);
        }

        WorkReview::factory()->create([
            'user_id' => $user->id,
            'quality_score' => 5,
            'reviewed_on' => now()->toDateString(),
        ]);

        $result = app(WorkProgressScore::class)->forUser($user);

        $this->assertTrue($result->enoughData);
        $this->assertNotNull($result->percent);
        $this->assertGreaterThanOrEqual(70, $result->percent);
    }

    public function test_attention_required_flags_overdue_goals(): void
    {
        $user = User::factory()->create(['name' => 'Rahul']);
        WorkGoal::factory()->overdue()->create([
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
            'title' => 'Homepage',
        ]);

        $alerts = app(WorkAttentionService::class)->forUser($user);

        $this->assertTrue($alerts->contains(fn (array $alert) => $alert['title'] === 'Overdue' && str_contains($alert['detail'], 'Rahul')));
    }

    public function test_staff_routes_are_permission_gated(): void
    {
        $user = $this->userWithPermissions(['work.my.view', 'work.my.manage']);

        $this->actingAs($user)->get(route('work.my'))->assertOk();
        $this->actingAs($user)->get(route('work.reviews'))->assertForbidden();
        $this->actingAs($user)->get(route('work.reports'))->assertForbidden();

        Livewire::actingAs($user)->test(TeamProgress::class)->assertForbidden();
    }

    public function test_employee_role_includes_own_work_permissions(): void
    {
        $this->seed();
        $role = Role::query()->where('slug', 'employee')->first();

        $this->assertTrue($role->permissions()->where('name', 'work.my.view')->exists());
        $this->assertTrue($role->permissions()->where('name', 'work.my.manage')->exists());
        $this->assertFalse($role->permissions()->where('name', 'work.team.view')->exists());
        $this->assertFalse($role->permissions()->where('name', 'dashboard.view')->exists());
    }

    public function test_staff_can_read_their_report_in_full(): void
    {
        $user = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $report = WorkDailyUpdate::factory()->create([
            'user_id' => $user->id,
            'accomplished' => "Called 8 people.\nPracticed Git.",
            'pending' => '2 numbers not connected.',
            'learned' => 'Route groups',
        ]);

        Livewire::actingAs($user)
            ->test(MyWork::class)
            ->call('read', $report->id)
            ->assertSet('tab', 'show')
            ->assertSet('readingId', $report->id)
            ->assertSee('What was done')
            ->assertSee('Called 8 people.')
            ->assertSee('Practiced Git.')
            ->assertSee('Pending')
            ->assertSee('2 numbers not connected.')
            ->assertSee('Learned')
            ->assertSee('Route groups');
    }

    public function test_staff_nav_shows_daily_progress_only(): void
    {
        $staff = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $items = app(NavigationService::class)->for($staff);
        $labels = collect($items)->pluck('label')->all();
        $work = collect($items)->firstWhere('label', 'Work');

        $this->assertNotContains('Dashboard', $labels);
        $this->assertSame(['Daily progress'], collect($work['children'])->pluck('label')->all());
        $this->assertSame('work.my', $staff->homeRoute());
    }

    public function test_manager_nav_shows_team_progress_when_they_have_reports(): void
    {
        $manager = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern->update(['manager_id' => $manager->id]);

        $work = collect(app(NavigationService::class)->for($manager))->firstWhere('label', 'Work');

        $this->assertSame(['Daily progress', 'Team progress'], collect($work['children'])->pluck('label')->all());
    }

    public function test_manager_sees_slipping_when_staff_missed_two_working_days(): void
    {
        Carbon::setTestNow('2026-10-08 12:00:00');

        $manager = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern->update(['manager_id' => $manager->id, 'name' => 'Asha Intern']);

        Livewire::actingAs($manager)
            ->test(TeamProgress::class)
            ->assertSee('Asha Intern')
            ->assertSee('Slipping');

        Carbon::setTestNow();
    }

    public function test_admin_can_delete_a_team_report(): void
    {
        $admin = $this->userWithPermissions(['work.my.view', 'work.my.manage', 'work.team.view']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $update = WorkDailyUpdate::factory()->create([
            'user_id' => $intern->id,
            'accomplished' => 'To be removed.',
        ]);

        Livewire::actingAs($admin)
            ->test(TeamProgress::class)
            ->assertSee('Delete')
            ->call('delete', $update->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('work_daily_updates', ['id' => $update->id]);
    }

    public function test_staff_manager_cannot_delete_reports(): void
    {
        $manager = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern = $this->userWithPermissions(['work.my.view', 'work.my.manage']);
        $intern->update(['manager_id' => $manager->id]);
        $update = WorkDailyUpdate::factory()->create([
            'user_id' => $intern->id,
            'accomplished' => 'Keep this.',
        ]);

        Livewire::actingAs($manager)
            ->test(TeamProgress::class)
            ->assertDontSee('Delete')
            ->call('delete', $update->id)
            ->assertForbidden();

        $this->assertDatabaseHas('work_daily_updates', ['id' => $update->id]);
    }
}
