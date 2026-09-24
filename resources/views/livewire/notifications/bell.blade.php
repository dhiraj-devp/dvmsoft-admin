<div class="relative" x-data="{ open: false }">
    <button type="button" class="relative rounded-xl p-2 text-ink-500 hover:bg-ink-50 dark:hover:bg-ink-800" @click="open = !open">
        <x-icon name="bell" />
        @if ($unread > 0)
            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-brand-500"></span>
        @endif
    </button>
    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-lg dark:border-ink-700 dark:bg-ink-900">
        <div class="flex items-center justify-between border-b border-ink-100 px-4 py-3 dark:border-ink-800">
            <p class="text-sm font-semibold">Notifications</p>
            @if ($unread > 0)
                <button type="button" class="text-xs font-medium text-brand-700" wire:click="markAll">Mark all read</button>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($latest as $item)
                @php
                    $payload = $item->data;
                    $title = $payload['title'] ?? 'Notification';
                    $message = $payload['message'] ?? '';
                    $url = $payload['url'] ?? null;
                @endphp
                <button type="button" class="block w-full px-4 py-3 text-left hover:bg-ink-50 dark:hover:bg-ink-800/40 {{ $item->read_at ? '' : 'bg-brand-50/40 dark:bg-brand-950/20' }}" wire:click="markRead('{{ $item->id }}')" @click="open = false">
                    @if ($url)
                        <a href="{{ $url }}" class="block" @click.stop>
                            <p class="text-sm font-medium">{{ $title }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">{{ \Illuminate\Support\Str::limit($message, 90) }}</p>
                        </a>
                    @else
                        <p class="text-sm font-medium">{{ $title }}</p>
                        <p class="mt-0.5 text-xs text-ink-500">{{ \Illuminate\Support\Str::limit($message, 90) }}</p>
                    @endif
                </button>
            @empty
                <p class="px-4 py-6 text-sm text-ink-500">You are all caught up.</p>
            @endforelse
        </div>
        <div class="flex justify-between border-t border-ink-100 px-4 py-2 text-xs dark:border-ink-800">
            <a href="{{ $inboxRoute }}" class="font-medium text-brand-700">View all</a>
            <a href="{{ $preferencesRoute }}" class="text-ink-500">Preferences</a>
        </div>
    </div>
</div>
