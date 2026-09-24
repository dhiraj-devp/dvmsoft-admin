<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->is_super_admin || $model->is($user)) {
            return false;
        }

        return $user->hasPermission('users.delete');
    }
}
