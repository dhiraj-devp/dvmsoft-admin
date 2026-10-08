<div class="space-y-4">
    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
    @endif

    <section class="card overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-3 dark:border-ink-800">
            <h2 class="font-semibold">This week</h2>
            <p class="text-sm text-ink-500">{{ $missingToday }} {{ $missingToday === 1 ? 'person has' : 'people have' }} no report today.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Person</th>
                        <th class="px-4 py-3 font-medium">Written</th>
                        <th class="px-4 py-3 font-medium">Done</th>
                        <th class="px-4 py-3 font-medium">Streak</th>
                        <th class="px-4 py-3 font-medium">This month</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($people as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                {{ $row['user']->name }}
                                @if ($row['growth']?->slipping)
                                    <x-badge class="ml-2" tone="warning">Slipping</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $row['growth']?->weekWritten ?? 0 }} / {{ $row['growth']?->weekExpected ?? 0 }}</td>
                            <td class="px-4 py-3">{{ $row['growth']?->weekDone ?? 0 }}</td>
                            <td class="px-4 py-3">{{ $row['growth']?->streak ?? 0 }}</td>
                            <td class="px-4 py-3 text-ink-500">{{ $row['growth']?->monthWritten ?? 0 }} / {{ $row['growth']?->monthExpected ?? 0 }} written</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state title="No team members" description="Set yourself as manager on staff users, or use a role that can view the whole team." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <select wire:model.live="status" class="input sm:max-w-[12rem]">
            <option value="in_review">In review</option>
            <option value="done">Done</option>
            <option value="all">All</option>
        </select>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-secondary" wire:click="selectVisible" @disabled($reviews->isEmpty())>Select page</button>
            <button type="button" class="btn-primary" wire:click="markSelectedDone" @disabled($selected === [])>
                Mark selected as done
            </button>
        </div>
    </div>
    @error('selected')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Select</span></th>
                        <th class="px-4 py-3 font-medium">Person</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Progress</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($reviews as $review)
                        <tr wire:key="{{ $review->id }}">
                            <td class="px-4 py-3">
                                @if (! $review->status->isDone())
                                    <input type="checkbox" wire:model.live="selected" value="{{ $review->id }}" class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $review->user?->name }}</td>
                            <td class="px-4 py-3">{{ $review->work_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-ink-600">{{ $review->preview() }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="$review->status->tone()">{{ $review->status->label() }}</x-badge>
                                @if ($review->manager_stamp)
                                    <x-badge class="ml-1" :tone="$review->manager_stamp->tone()">{{ $review->manager_stamp->label() }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" class="text-sm font-medium text-brand-700" wire:click="read('{{ $review->id }}')">Read</button>
                                @if (! $review->status->isDone())
                                    @can('markDone', $review)
                                        <button type="button" class="ml-3 text-sm font-medium text-brand-700" wire:click="markDone('{{ $review->id }}', 'good')">Good</button>
                                        <button type="button" class="ml-3 text-sm font-medium text-amber-700" wire:click="markDone('{{ $review->id }}', 'needs_follow_up')">Follow-up</button>
                                    @endcan
                                @endif
                                @can('delete', $review)
                                    <button type="button" class="ml-3 text-sm font-medium text-red-600" @click="$dispatch('confirm', { id: '{{ $review->id }}', event: 'confirmed-delete-work-update', title: 'Delete report?', message: 'This day’s report will be removed. The person can write it again.' })">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state title="Nothing to review" description="When staff save a daily report, it will appear here." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reviews->hasPages())
            <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $reviews->links() }}</div>
        @endif
    </div>

    <div x-show="$wire.readingId" x-cloak class="fixed inset-0 z-40 flex justify-end bg-ink-950/40">
        <div class="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-2xl dark:bg-ink-900" @click.outside="$wire.closeRead()">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ $reading?->user?->name }}</h2>
                <button type="button" wire:click="closeRead"><x-icon name="x" /></button>
            </div>
            @if ($reading)
                <x-work-report-reader :report="$reading">
                    @if (! $reading->status->isDone())
                        @can('markDone', $reading)
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="btn-primary" wire:click="markDone('{{ $reading->id }}', 'good')">Mark good</button>
                                <button type="button" class="btn-secondary" wire:click="markDone('{{ $reading->id }}', 'needs_follow_up')">Needs follow-up</button>
                            </div>
                        @endcan
                    @elseif ($reading->reviewer)
                        <p class="text-sm text-ink-500">Marked done by {{ $reading->reviewer->name }}{{ $reading->reviewed_at ? ' on '.$reading->reviewed_at->format('d M Y') : '' }}.</p>
                    @endif
                    @can('delete', $reading)
                        <button type="button" class="btn-danger" @click="$dispatch('confirm', { id: '{{ $reading->id }}', event: 'confirmed-delete-work-update', title: 'Delete report?', message: 'This day’s report will be removed. The person can write it again.' })">Delete</button>
                    @endcan
                </x-work-report-reader>
            @endif
        </div>
    </div>
</div>
