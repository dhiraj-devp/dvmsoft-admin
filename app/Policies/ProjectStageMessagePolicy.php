<?php

namespace App\Policies;

use App\Models\ProjectStageMessage;
use App\Models\ProjectStageMessageAttachment;
use App\Models\User;

class ProjectStageMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.stage_discussion.view');
    }

    public function view(User $user, ProjectStageMessage $message): bool
    {
        return $user->hasPermission('projects.stage_discussion.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.stage_discussion.create');
    }

    public function viewAttachment(User $user, ProjectStageMessageAttachment $attachment): bool
    {
        $attachment->loadMissing('message');

        if ($attachment->message?->is_internal && ! $user->hasPermission('projects.stage_discussion.view')) {
            return false;
        }

        return $user->hasPermission('projects.stage_discussion.view');
    }
}
