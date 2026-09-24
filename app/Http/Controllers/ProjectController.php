<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Project::class);

        return view('projects.index');
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $tab = request()->string('tab', 'overview')->toString();
        $allowed = ['overview', 'delivery', 'tasks', 'milestones', 'requirements', 'change-requests', 'team', 'documents', 'activity'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'overview';
        }

        $project->load([
            'client',
            'quotation',
            'manager',
            'members.user',
            'milestones',
            'stages.owner',
            'tasks.assignedUser',
            'changeRequests',
            'invoices',
        ]);

        return view('projects.show', [
            'project' => $project,
            'tab' => $tab,
        ]);
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }
}
