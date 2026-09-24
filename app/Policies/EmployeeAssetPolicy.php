<?php

namespace App\Policies;

use App\Models\EmployeeAsset;
use App\Models\User;

class EmployeeAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('assets.view');
    }

    public function view(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('assets.create') || $user->hasPermission('assets.assign');
    }

    public function assign(User $user): bool
    {
        return $user->hasPermission('assets.assign') || $user->hasPermission('assets.create');
    }

    public function return(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.return') && $asset->return_date === null;
    }

    public function delete(User $user, EmployeeAsset $asset): bool
    {
        return $user->hasPermission('assets.create');
    }
}
