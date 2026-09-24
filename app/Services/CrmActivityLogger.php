<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CrmActivityLogger
{
    public function log(
        Model $subject,
        string $type,
        string $title,
        ?string $body = null,
        array $meta = [],
        ?User $user = null,
    ): CrmActivity {
        return $subject->activities()->create([
            'user_id' => $user?->id ?? Auth::guard('web')->id(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
