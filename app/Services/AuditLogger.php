<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClientUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    public function record(
        string $action,
        string $module,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Authenticatable $user = null,
    ): AuditLog {
        $hidden = config('dvmsoft.audit_hidden', []);
        $staffId = null;
        $clientUserId = null;

        if ($user instanceof User) {
            $staffId = $user->id;
        } elseif ($user instanceof ClientUser) {
            $clientUserId = $user->id;
        } else {
            $staffId = Auth::guard('web')->id();
            $clientUserId = Auth::guard('client')->id();
        }

        return AuditLog::query()->create([
            'user_id' => $staffId,
            'client_user_id' => $clientUserId,
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ? collect($oldValues)->except($hidden)->all() : null,
            'new_values' => $newValues ? collect($newValues)->except($hidden)->all() : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
