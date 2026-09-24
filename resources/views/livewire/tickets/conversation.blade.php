<div class="space-y-6">
    <section class="card p-5">
        <div class="grid gap-4 md:grid-cols-2">
            @can('assign', $ticket)
                <form wire:submit="assign" class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="label">Assigned support user</label>
                        <select wire:model="assignedToId" class="input">
                            <option value="">Unassigned</option>
                            @foreach ($assignees as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary">Assign</button>
                </form>
            @endcan
            @can('update', $ticket)
                <form wire:submit="changeStatus" class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="label">Status</label>
                        <select wire:model="status" class="input">
                            @foreach ($openStatuses as $item)
                                <option value="{{ $item->value }}">{{ $item->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-secondary">Update</button>
                </form>
            @endcan
        </div>
        @can('resolve', $ticket)
            <form wire:submit="resolve" class="mt-4 space-y-3">
                <div>
                    <label class="label">Resolution</label>
                    <textarea wire:model="resolution" rows="3" class="input" placeholder="What was done?"></textarea>
                    @error('resolution')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-primary">Mark resolved</button>
            </form>
        @endcan
        @can('close', $ticket)
            <form wire:submit="close" class="mt-3">
                <button type="submit" class="btn-secondary">Close ticket</button>
            </form>
        @endcan
    </section>

    <section class="card p-5">
        <h2 class="font-semibold">Conversation</h2>
        <div class="mt-4 space-y-4">
            @forelse ($messages as $message)
                <article class="rounded-2xl border px-4 py-3 {{ $message->is_internal ? 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40' : 'border-ink-200 dark:border-ink-700' }}">
                    <div class="flex items-center justify-between text-xs text-ink-500">
                        <span class="font-medium text-ink-800 dark:text-ink-100">{{ $message->displayAuthorName() }}</span>
                        <span>
                            {{ $message->created_at?->format('d M Y H:i') }}
                            @if ($message->is_internal)
                                <x-badge tone="warning">Internal</x-badge>
                            @endif
                        </span>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                    @foreach ($message->attachments as $attachment)
                        <a href="{{ route('tickets.attachments.download', [$ticket, $message, $attachment]) }}" class="mt-2 inline-block text-sm font-medium text-brand-700">{{ $attachment->original_name }} ({{ $attachment->humanSize() }})</a>
                    @endforeach
                </article>
            @empty
                <x-empty-state title="No replies yet" />
            @endforelse
        </div>
    </section>

    @can('reply', $ticket)
        <form wire:submit="reply" class="card space-y-4 p-5">
            <div>
                <label class="label">{{ $internal ? 'Internal note' : 'Reply' }}</label>
                <textarea wire:model="body" rows="4" class="input"></textarea>
                @error('body')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Attachment</label>
                <input type="file" wire:model="upload" class="input">
            </div>
            @if ($canSeeInternal)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="internal" class="rounded border-ink-300 text-brand-700">
                    Internal note (never visible to clients)
                </label>
            @endif
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">{{ $internal ? 'Save internal note' : 'Send reply' }}</button>
            </div>
        </form>
    @endcan
</div>
