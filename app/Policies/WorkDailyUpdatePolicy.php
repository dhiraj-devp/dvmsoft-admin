<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkDailyUpdate;

class WorkDailyUpdatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work.my.view') || $user->hasPermission('work.team.view');
    }

    public function view(User $user, WorkDailyUpdate $update): bool
    {
        if ($user->hasPermission('work.team.view') || $user->hasPermission('work.reviews.view')) {
            return true;
        }

        if ($update->user?->manager_id === $user->id) {
            return true;
        }

        return $user->hasPermission('work.my.view') && $update->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work.my.manage');
    }

    public function update(User $user, WorkDailyUpdate $update): bool
    {
        if ($update->status?->isDone()) {
            return false;
        }

        return $user->hasPermission('work.my.manage') && $update->user_id === $user->id;
    }

    public function markDone(User $user, WorkDailyUpdate $update): bool
    {
        if ($user->hasPermission('work.team.view')) {
            return true;
        }

        return $update->user?->manager_id === $user->id;
    }

    public function delete(User $user, WorkDailyUpdate $update): bool
    {
        return $user->hasPermission('work.team.view');
    }
}
