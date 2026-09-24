<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Bell extends Component
{
    public string $guard = 'web';

    public function markRead(string $id): void
    {
        $notification = $this->user()?->notifications()->whereKey($id)->first();
        $notification?->markAsRead();
    }

    public function markAll(): void
    {
        $this->user()?->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        $user = $this->user();
        $unread = $user ? $user->unreadNotifications()->count() : 0;
        $latest = $user
            ? $user->notifications()->latest()->limit(8)->get()
            : collect();

        return view('livewire.notifications.bell', [
            'unread' => $unread,
            'latest' => $latest,
            'inboxRoute' => $this->guard === 'client' ? route('client.notifications.index') : route('notifications.index'),
            'preferencesRoute' => $this->guard === 'client' ? route('client.notifications.preferences') : route('notifications.preferences'),
        ]);
    }

    protected function user(): mixed
    {
        return auth($this->guard)->user();
    }
}
