<?php

namespace App\Policies;

use App\Models\DocumentType;
use App\Models\User;

class DocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.manage_types') || $user->hasPermission('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage_types');
    }

    public function update(User $user, DocumentType $type): bool
    {
        return $user->hasPermission('documents.manage_types');
    }

    public function delete(User $user, DocumentType $type): bool
    {
        return $user->hasPermission('documents.manage_types');
    }
}
