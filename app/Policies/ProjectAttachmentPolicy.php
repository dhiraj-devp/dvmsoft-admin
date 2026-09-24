<?php

namespace App\Policies;

use App\Models\ProjectAttachment;
use App\Models\User;

class ProjectAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, ProjectAttachment $attachment): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.upload');
    }

    public function delete(User $user, ProjectAttachment $attachment): bool
    {
        return $user->hasPermission('projects.upload');
    }
}
