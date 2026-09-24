<?php

namespace App\Policies;

use App\Models\Requirement;
use App\Models\User;

class RequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('requirements.view');
    }

    public function view(User $user, Requirement $requirement): bool
    {
        return $user->hasPermission('requirements.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('requirements.create');
    }

    public function update(User $user, Requirement $requirement): bool
    {
        return $user->hasPermission('requirements.edit');
    }

    public function delete(User $user, Requirement $requirement): bool
    {
        return $user->hasPermission('requirements.delete');
    }

    public function approve(User $user, Requirement $requirement): bool
    {
        return $user->hasPermission('requirements.approve');
    }
}
