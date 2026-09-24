<?php

namespace App\Policies;

use App\Models\ProjectStage;
use App\Models\User;

class ProjectStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.stages.view');
    }

    public function view(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.stages.manage');
    }

    public function update(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.manage');
    }

    public function delete(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.manage');
    }

    public function assign(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.assign');
    }

    public function submitReview(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.submit_review');
    }

    public function complete(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stages.complete');
    }

    public function overrideApproval(User $user, ProjectStage $stage): bool
    {
        return $user->hasPermission('projects.stage_approval.override');
    }
}
