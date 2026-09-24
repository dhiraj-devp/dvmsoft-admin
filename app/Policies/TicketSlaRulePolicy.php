<?php

namespace App\Policies;

use App\Models\TicketSlaRule;
use App\Models\User;

class TicketSlaRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.manage_sla') || $user->hasPermission('support.dashboard.view');
    }

    public function update(User $user, TicketSlaRule $rule): bool
    {
        return $user->hasPermission('tickets.manage_sla');
    }
}
