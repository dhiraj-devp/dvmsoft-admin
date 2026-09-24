<?php

namespace App\Policies;

use App\Models\ProjectStageEvidence;
use App\Models\User;

class ProjectStageEvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.stage_evidence.view');
    }

    public function view(User $user, ProjectStageEvidence $evidence): bool
    {
        return $user->hasPermission('projects.stage_evidence.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.stage_evidence.manage');
    }

    public function delete(User $user, ProjectStageEvidence $evidence): bool
    {
        return $user->hasPermission('projects.stage_evidence.manage');
    }
}
