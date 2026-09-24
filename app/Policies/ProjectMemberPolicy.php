<?php

namespace App\Policies;

use App\Models\ProjectMember;
use App\Models\User;

class ProjectMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.manage_team');
    }

    public function update(User $user, ProjectMember $member): bool
    {
        return $user->hasPermission('projects.manage_team');
    }

    public function delete(User $user, ProjectMember $member): bool
    {
        return $user->hasPermission('projects.manage_team');
    }
}
