<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkDailyPlan;

class WorkDailyPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work.my.view') || $user->hasPermission('work.team.view');
    }

    public function view(User $user, WorkDailyPlan $plan): bool
    {
        if ($user->hasPermission('work.team.view')) {
            return true;
        }

        return $user->hasPermission('work.my.view') && $plan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work.my.manage');
    }

    public function update(User $user, WorkDailyPlan $plan): bool
    {
        return $user->hasPermission('work.my.manage') && $plan->user_id === $user->id;
    }

    public function delete(User $user, WorkDailyPlan $plan): bool
    {
        return $user->hasPermission('work.my.manage') && $plan->user_id === $user->id;
    }
}
