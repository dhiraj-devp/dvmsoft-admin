<?php

namespace App\Policies;

use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    public function view(User $user, DocumentVersion $version): bool
    {
        return $user->hasPermission('documents.download');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage_versions') || $user->hasPermission('documents.upload');
    }
}
