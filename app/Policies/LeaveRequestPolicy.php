<?php

namespace App\Policies;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('leave.view');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('leave.create');
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.approve') && $leaveRequest->status === LeaveRequestStatus::Pending;
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.reject') && $leaveRequest->status === LeaveRequestStatus::Pending;
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermission('leave.create')
            && in_array($leaveRequest->status, [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved], true);
    }
}
