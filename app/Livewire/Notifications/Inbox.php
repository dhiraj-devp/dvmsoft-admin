<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Inbox extends Component
{
    use WithPagination;

    public string $guard = 'web';

    public function markRead(string $id): void
    {
        $notification = auth($this->guard)->user()?->notifications()->whereKey($id)->first();
        $notification?->markAsRead();
    }

    public function markAll(): void
    {
        auth($this->guard)->user()?->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        $user = auth($this->guard)->user();

        return view('livewire.notifications.inbox', [
            'notifications' => $user
                ? $user->notifications()->latest()->paginate(20, pageName: 'notificationsPage')
                : collect(),
        ]);
    }
}
