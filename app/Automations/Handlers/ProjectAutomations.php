<?php

namespace App\Automations\Handlers;

use App\Automations\AutomationResult;
use App\Automations\Contracts\AutomationHandler;
use App\Automations\StaffNotifier;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectHealth;
use App\Enums\StageStatus;
use App\Enums\TaskStatus;
use App\Models\Automation;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectAutomations implements AutomationHandler
{
    public function __construct(protected StaffNotifier $staff) {}

    public function handle(Automation $automation): AutomationResult
    {
        return match ($automation->key) {
            'projects.milestone_upcoming' => $this->milestoneUpcoming($automation),
            'projects.milestone_overdue' => $this->milestoneOverdue($automation),
            'projects.task_overdue' => $this->taskOverdue($automation),
            'projects.task_blocked' => $this->taskBlocked($automation),
            'projects.deadline_approaching' => $this->deadlineApproaching($automation),
            'projects.health_risk' => $this->healthRisk($automation),
            'projects.stage_overdue' => $this->stageOverdue($automation),
            default => new AutomationResult,
        };
    }

    protected function milestoneUpcoming(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.milestone_upcoming_days', 3));
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $milestones = Milestone::query()
            ->with(['project.manager'])
            ->where('status', '!=', MilestoneStatus::Completed->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays($days)->toDateString())
            ->get();

        foreach ($milestones as $milestone) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $this->projectRecipients($milestone->project),
                'Upcoming milestone',
                $milestone->name.' on '.$milestone->project?->name.' is due '.$milestone->due_date->format($format).'.',
                $milestone->project ? url(route('projects.show', $milestone->project, false)) : null,
                ['milestone_id' => $milestone->id],
                $milestone,
                $milestone->due_date->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function milestoneOverdue(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $milestones = Milestone::query()
            ->with(['project.manager'])
            ->where('status', '!=', MilestoneStatus::Completed->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($milestones as $milestone) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $this->projectRecipients($milestone->project),
                'Overdue milestone',
                $milestone->name.' on '.$milestone->project?->name.' was due '.$milestone->due_date->format($format).'.',
                $milestone->project ? url(route('projects.show', $milestone->project, false)) : null,
                ['milestone_id' => $milestone->id],
                $milestone,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function taskOverdue(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $tasks = Task::query()
            ->with(['project.manager', 'assignedUser'])
            ->open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($tasks as $task) {
            $result->processed++;
            $users = collect([$task->assignedUser, $task->project?->manager])->filter();

            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('tasks.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Overdue task',
                $task->title.' was due '.$task->due_date->format($format).'.',
                $task->project ? url(route('projects.show', $task->project, false)) : null,
                ['task_id' => $task->id],
                $task,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function taskBlocked(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;

        $tasks = Task::query()
            ->with(['project.manager', 'assignedUser'])
            ->where('status', TaskStatus::Blocked->value)
            ->get();

        foreach ($tasks as $task) {
            $result->processed++;
            $users = collect([$task->assignedUser, $task->project?->manager])->filter();

            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('tasks.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Blocked task',
                $task->title.' is blocked'.($task->project ? ' on '.$task->project->name : '').'.',
                $task->project ? url(route('projects.show', $task->project, false)) : null,
                ['task_id' => $task->id],
                $task,
                now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function deadlineApproaching(Automation $automation): AutomationResult
    {
        $days = max(1, (int) settings('automations.project_deadline_days', 7));
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $projects = Project::query()
            ->with('manager')
            ->open()
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '>=', now()->toDateString())
            ->whereDate('expected_end_date', '<=', now()->addDays($days)->toDateString())
            ->get();

        foreach ($projects as $project) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $this->projectRecipients($project),
                'Project deadline approaching',
                $project->name.' is expected to finish on '.$project->expected_end_date->format($format).'.',
                url(route('projects.show', $project, false)),
                ['project_id' => $project->id],
                $project,
                $project->expected_end_date->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function healthRisk(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;

        $projects = Project::query()
            ->with('manager')
            ->open()
            ->whereIn('health', [ProjectHealth::Yellow->value, ProjectHealth::Red->value])
            ->get();

        foreach ($projects as $project) {
            $result->processed++;
            $result->notified += $this->staff->send(
                $automation->key,
                $this->projectRecipients($project),
                'Project health alert',
                $project->name.' health is '.$project->health->label().'.',
                url(route('projects.show', $project, false)),
                ['project_id' => $project->id, 'health' => $project->health->value],
                $project,
                $project->health->value.'-'.now()->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    protected function stageOverdue(Automation $automation): AutomationResult
    {
        $result = new AutomationResult;
        $format = settings('company.date_format', 'd M Y');

        $stages = ProjectStage::query()
            ->with(['project.manager', 'owner'])
            ->where('status', '!=', StageStatus::Completed->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($stages as $stage) {
            $result->processed++;
            $users = collect([$stage->owner, $stage->project?->manager])->filter()->unique(fn (User $user) => $user->id);
            if ($users->isEmpty()) {
                $users = $this->staff->withPermission('projects.stages.view');
            }

            $result->notified += $this->staff->send(
                $automation->key,
                $users,
                'Overdue stage',
                $stage->name.' on '.$stage->project?->name.' was due '.$stage->due_date->format($format).'.',
                $stage->project ? url(route('projects.show', ['project' => $stage->project, 'tab' => 'delivery'], false)) : null,
                ['stage_id' => $stage->id],
                $stage,
                $stage->due_date->toDateString(),
                $automation->channels,
            );
        }

        return $result;
    }

    /**
     * @return Collection<int, User>
     */
    protected function projectRecipients(?Project $project)
    {
        $users = collect([$project?->manager])->filter();

        if ($users->isEmpty()) {
            return $this->staff->withPermission('projects.view');
        }

        return $users;
    }
}
