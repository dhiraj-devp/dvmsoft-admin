<?php

namespace App\Policies;

use App\Enums\ChangeRequestStatus;
use App\Models\ChangeRequest;
use App\Models\User;

class ChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('change_requests.view');
    }

    public function view(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermission('change_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('change_requests.create');
    }

    public function update(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermission('change_requests.edit')
            && $changeRequest->status === ChangeRequestStatus::Pending;
    }

    public function delete(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermission('change_requests.delete')
            && $changeRequest->status === ChangeRequestStatus::Pending;
    }

    public function approve(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermission('change_requests.approve')
            && $changeRequest->status === ChangeRequestStatus::Pending;
    }

    public function implement(User $user, ChangeRequest $changeRequest): bool
    {
        return $user->hasPermission('change_requests.edit')
            && $changeRequest->status === ChangeRequestStatus::Approved;
    }
}
