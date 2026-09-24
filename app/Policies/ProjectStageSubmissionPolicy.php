<?php

namespace App\Policies;

use App\Models\ProjectStageSubmission;
use App\Models\User;

class ProjectStageSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.stage_reviews.view');
    }

    public function view(User $user, ProjectStageSubmission $submission): bool
    {
        return $user->hasPermission('projects.stage_reviews.view');
    }

    public function manage(User $user, ProjectStageSubmission $submission): bool
    {
        return $user->hasPermission('projects.stage_reviews.manage');
    }
}
