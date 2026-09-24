<?php

namespace App\Automations;

use App\Models\Client;
use App\Models\ClientUser;
use App\Notifications\ClientPortalEventNotification;
use Illuminate\Database\Eloquent\Model;

class ClientPortalNotifier
{
    public function __construct(
        protected AutomationEngine $engine,
        protected DispatchLog $dispatches,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notify(
        ?Client $client,
        string $key,
        string $event,
        string $title,
        string $message,
        ?string $url = null,
        array $payload = [],
        Model|string|null $subject = null,
        ?string $occurrence = null,
        ?ClientUser $except = null,
    ): int {
        if (! $client || ! $this->engine->isEnabled($key)) {
            return 0;
        }

        $occurrence ??= $event;

        if ($subject && ! $this->dispatches->claimed($key, $subject, $occurrence)) {
            return 0;
        }

        $users = $client->portalUsers()
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->where('id', '!=', $except->id))
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            $user->notify(new ClientPortalEventNotification($event, $title, $message, $url, $payload));
            $sent++;
        }

        return $sent;
    }
}
