<?php

namespace App\Policies;

use App\Models\TicketCategory;
use App\Models\User;

class TicketCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tickets.manage_categories') || $user->hasPermission('tickets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tickets.manage_categories');
    }

    public function update(User $user, TicketCategory $category): bool
    {
        return $user->hasPermission('tickets.manage_categories');
    }

    public function delete(User $user, TicketCategory $category): bool
    {
        return $user->hasPermission('tickets.manage_categories');
    }
}
