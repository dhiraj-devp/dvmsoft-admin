<?php

namespace App\Policies;

use App\Models\Automation;
use App\Models\User;

class AutomationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('automations.view');
    }

    public function view(User $user, Automation $automation): bool
    {
        return $user->hasPermission('automations.view');
    }

    public function update(User $user, Automation $automation): bool
    {
        return $user->hasPermission('automations.manage');
    }

    public function viewHistory(User $user, Automation $automation): bool
    {
        return $user->hasPermission('automations.history.view');
    }
}
