<div>
    <div class="mb-4 flex justify-end">
        @if (auth($guard)->user()?->unreadNotifications->isNotEmpty())
            <button type="button" class="text-sm font-medium text-brand-700" wire:click="markAll">Mark all as read</button>
        @endif
    </div>
    <div class="card divide-y divide-ink-100 dark:divide-ink-800">
        @forelse ($notifications as $item)
            @php
                $payload = $item->data;
                $title = $payload['title'] ?? 'Notification';
                $message = $payload['message'] ?? '';
                $url = $payload['url'] ?? null;
            @endphp
            <div class="flex items-start justify-between gap-4 px-5 py-4 {{ $item->read_at ? '' : 'bg-brand-50/30 dark:bg-brand-950/20' }}" wire:key="{{ $item->id }}">
                <div>
                    <p class="font-medium">{{ $title }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $message }}</p>
                    <p class="mt-2 text-xs text-ink-400">{{ $item->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3 text-sm">
                    @if ($url)
                        <a href="{{ $url }}" class="font-medium text-brand-700">Open</a>
                    @endif
                    @unless ($item->read_at)
                        <button type="button" class="text-ink-500" wire:click="markRead('{{ $item->id }}')">Mark read</button>
                    @endunless
                </div>
            </div>
        @empty
            <x-empty-state title="No notifications yet" />
        @endforelse
    </div>
    @if (method_exists($notifications, 'hasPages') && $notifications->hasPages())
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
