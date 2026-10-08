<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkReview;

class WorkReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('work.reviews.view') || $user->hasPermission('work.my.view');
    }

    public function view(User $user, WorkReview $review): bool
    {
        if ($user->hasPermission('work.reviews.view')) {
            return true;
        }

        return $user->hasPermission('work.my.view') && $review->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('work.reviews.manage');
    }

    public function update(User $user, WorkReview $review): bool
    {
        return $user->hasPermission('work.reviews.manage');
    }

    public function delete(User $user, WorkReview $review): bool
    {
        return $user->hasPermission('work.reviews.manage');
    }
}
