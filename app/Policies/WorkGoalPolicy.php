<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkGoal;

class WorkGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work.goals.view');
    }

    public function view(User $user, WorkGoal $goal): bool
    {
        if ($user->hasPermission('work.team.view') || $user->hasPermission('work.goals.manage')) {
            return true;
        }

        return $user->hasPermission('work.goals.view') && $goal->assigned_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work.goals.manage');
    }

    public function update(User $user, WorkGoal $goal): bool
    {
        if ($user->hasPermission('work.goals.manage')) {
            return true;
        }

        return $user->hasPermission('work.my.manage') && $goal->assigned_user_id === $user->id;
    }

    public function delete(User $user, WorkGoal $goal): bool
    {
        return $user->hasPermission('work.goals.manage');
    }
}
