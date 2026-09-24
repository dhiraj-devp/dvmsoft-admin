<?php

namespace App\Automations;

use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Collection;

class StaffNotifier
{
    public function __construct(protected DispatchLog $dispatches) {}

    /**
     * @param  Collection<int, User>|iterable<User>  $users
     * @param  array<string, mixed>  $meta
     */
    public function send(
        string $automationKey,
        iterable $users,
        string $title,
        string $message,
        ?string $url = null,
        array $meta = [],
        mixed $subject = null,
        ?string $occurrence = null,
        ?array $channels = null,
    ): int {
        $occurrence ??= now()->toDateString();

        if ($subject && ! $this->dispatches->claimed($automationKey, $subject, $occurrence)) {
            return 0;
        }

        $sent = 0;

        foreach (collect($users)->unique(fn (User $user) => $user->id) as $user) {
            if (! $user->is_active) {
                continue;
            }

            $user->notify(new OperationalNotification($title, $message, $url, $meta, $channels));
            $sent++;
        }

        return $sent;
    }

    /**
     * @return Collection<int, User>
     */
    public function withPermission(string $permission): Collection
    {
        return User::query()
            ->active()
            ->where(function ($query) use ($permission) {
                $query->where('is_super_admin', true)
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('name', $permission));
            })
            ->get();
    }
}
