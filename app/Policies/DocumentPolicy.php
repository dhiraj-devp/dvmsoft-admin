<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.edit') && $document->status !== DocumentStatus::Archived;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.delete');
    }

    public function upload(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.upload') || $user->hasPermission('documents.manage_versions');
    }

    public function download(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.download');
    }

    public function submit(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.submit') && $document->status === DocumentStatus::Draft;
    }

    public function approve(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.approve') && $document->status === DocumentStatus::Review;
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.archive')
            && ! in_array($document->status, [DocumentStatus::Draft, DocumentStatus::Review, DocumentStatus::Archived], true);
    }
}
