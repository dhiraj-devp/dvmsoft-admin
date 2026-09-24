<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('milestones.view');
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $user->hasPermission('milestones.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('milestones.create');
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $user->hasPermission('milestones.edit');
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->hasPermission('milestones.delete');
    }
}
