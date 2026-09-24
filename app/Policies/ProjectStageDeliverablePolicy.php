<?php

namespace App\Policies;

use App\Models\ProjectStageDeliverable;
use App\Models\User;

class ProjectStageDeliverablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.stage_deliverables.view');
    }

    public function view(User $user, ProjectStageDeliverable $deliverable): bool
    {
        return $user->hasPermission('projects.stage_deliverables.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.stage_deliverables.manage');
    }

    public function update(User $user, ProjectStageDeliverable $deliverable): bool
    {
        return $user->hasPermission('projects.stage_deliverables.manage');
    }

    public function delete(User $user, ProjectStageDeliverable $deliverable): bool
    {
        return $user->hasPermission('projects.stage_deliverables.manage');
    }
}
