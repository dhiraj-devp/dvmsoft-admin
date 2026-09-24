<?php

namespace App\Services\Ai;

use App\Enums\ChangeRequestStatus;
use App\Enums\MilestoneStatus;
use App\Enums\TaskStatus;
use App\Models\Project;

class ProjectAssistant extends Assistant
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(Project $project): array
    {
        $project->loadMissing(['client', 'manager', 'tasks.assignedUser', 'milestones', 'changeRequests']);

        return $this->generate('project', $this->context($project), $project);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(Project $project): array
    {
        $tasks = $project->tasks;

        return [
            'project' => [
                'number' => $project->number,
                'name' => $project->name,
                'status' => $project->status->value,
                'health' => $project->health->value,
                'priority' => $project->priority->value,
                'progress_percent' => $project->progressPercent(),
                'start_date' => optional($project->start_date)?->toDateString(),
                'expected_end_date' => optional($project->expected_end_date)?->toDateString(),
                'manager' => $project->manager?->name,
                'client' => $project->client?->name,
            ],
            'overdue_tasks' => $tasks->filter(fn ($task) => $task->isOverdue())->take(12)->map(fn ($task) => [
                'title' => $task->title,
                'due_date' => optional($task->due_date)?->toDateString(),
                'owner' => $task->assignedUser?->name,
                'status' => $task->status->value,
            ])->values()->all(),
            'blocked_tasks' => $tasks->where('status', TaskStatus::Blocked)->take(12)->map(fn ($task) => [
                'title' => $task->title,
                'owner' => $task->assignedUser?->name,
            ])->values()->all(),
            'milestones' => $project->milestones->take(12)->map(fn ($milestone) => [
                'name' => $milestone->name,
                'status' => $milestone->status->value,
                'due_date' => optional($milestone->due_date)?->toDateString(),
                'completion_percentage' => $milestone->completion_percentage,
                'overdue' => $milestone->status !== MilestoneStatus::Completed
                    && $milestone->due_date
                    && $milestone->due_date->isPast(),
            ])->all(),
            'change_requests' => $project->changeRequests->take(12)->map(fn ($changeRequest) => [
                'number' => $changeRequest->number,
                'title' => $changeRequest->title,
                'status' => $changeRequest->status->value,
                'impact_on_timeline_days' => $changeRequest->impact_on_timeline_days,
                'pending' => $changeRequest->status === ChangeRequestStatus::Pending,
            ])->all(),
        ];
    }
}
