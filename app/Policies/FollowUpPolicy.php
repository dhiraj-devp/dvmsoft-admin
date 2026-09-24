<?php

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('follow_ups.view');
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow_ups.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('follow_ups.create');
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow_ups.edit');
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow_ups.delete');
    }

    public function complete(User $user, FollowUp $followUp): bool
    {
        return $user->hasPermission('follow_ups.complete');
    }
}
