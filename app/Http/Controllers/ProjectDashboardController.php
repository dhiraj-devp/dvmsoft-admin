<?php

namespace App\Http\Controllers;

use App\Enums\ChangeRequestStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Models\ChangeRequest;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use Illuminate\View\View;

class ProjectDashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query();

        return view('projects.dashboard', [
            'metrics' => [
                ['label' => 'Total projects', 'value' => (clone $projects)->count(), 'hint' => 'All delivery work', 'icon' => 'folder'],
                ['label' => 'Active', 'value' => Project::query()->where('status', ProjectStatus::Active)->count(), 'hint' => 'In delivery now', 'icon' => 'check'],
                ['label' => 'On hold', 'value' => Project::query()->where('status', ProjectStatus::OnHold)->count(), 'hint' => 'Paused work', 'icon' => 'clock'],
                ['label' => 'Completed', 'value' => Project::query()->where('status', ProjectStatus::Completed)->count(), 'hint' => 'Closed projects', 'icon' => 'check'],
                ['label' => 'At risk', 'value' => Project::query()->where('health', 'red')->count(), 'hint' => 'Health is red', 'icon' => 'alert'],
                ['label' => 'Open change requests', 'value' => ChangeRequest::query()->where('status', ChangeRequestStatus::Pending)->count(), 'hint' => 'Waiting a decision', 'icon' => 'document'],
                ['label' => 'Open tasks', 'value' => Task::query()->open()->count(), 'hint' => 'Still in flight', 'icon' => 'folder'],
                ['label' => 'Upcoming milestones', 'value' => Milestone::query()->whereDate('due_date', '>=', now())->where('status', '!=', 'completed')->count(), 'hint' => 'Due from today', 'icon' => 'clock'],
                ['label' => 'Awaiting client review', 'value' => ProjectStage::query()->where('status', StageStatus::ReadyForReview->value)->count(), 'hint' => 'Stages ready for review', 'icon' => 'document'],
                ['label' => 'Changes requested', 'value' => ProjectStage::query()->where('status', StageStatus::ChangesRequested->value)->count(), 'hint' => 'Client sent work back', 'icon' => 'alert'],
                ['label' => 'Overdue stages', 'value' => ProjectStage::query()->where('status', '!=', StageStatus::Completed->value)->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString())->count(), 'hint' => 'Past due date', 'icon' => 'clock'],
                ['label' => 'Blocked stages', 'value' => ProjectStage::query()->where('status', StageStatus::Blocked->value)->count(), 'hint' => 'Delivery is stuck', 'icon' => 'alert'],
            ],
            'recentProjects' => Project::query()->with('client')->latest()->limit(6)->get(),
            'awaitingReview' => ProjectStage::query()
                ->with('project')
                ->where('status', StageStatus::ReadyForReview->value)
                ->orderBy('updated_at', 'desc')
                ->limit(6)
                ->get(),
            'changesRequested' => ProjectStage::query()
                ->with('project')
                ->where('status', StageStatus::ChangesRequested->value)
                ->orderBy('updated_at', 'desc')
                ->limit(6)
                ->get(),
            'myTasks' => Task::query()
                ->with('project')
                ->where('assigned_user_id', auth()->id())
                ->open()
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
            'upcomingDeadlines' => Task::query()
                ->with('project')
                ->open()
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->limit(6)
                ->get(),
        ]);
    }
}
