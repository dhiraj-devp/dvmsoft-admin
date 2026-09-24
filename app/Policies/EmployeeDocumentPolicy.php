<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('employee_documents.view');
    }

    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->hasPermission('employee_documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('employee_documents.upload');
    }

    public function delete(User $user, EmployeeDocument $document): bool
    {
        return $user->hasPermission('employee_documents.delete');
    }
}
