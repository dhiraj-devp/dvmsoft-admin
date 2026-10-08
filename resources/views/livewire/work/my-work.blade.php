<div class="space-y-4">
    @if (session('status'))
        <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
    @endif

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            wire:click="openTab('analytics')"
            class="rounded-full px-4 py-2 text-sm {{ $tab === 'analytics' ? 'bg-brand-700 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50 dark:bg-ink-900 dark:text-ink-300 dark:ring-ink-700' }}"
        >
            Analytics
        </button>
        <button
            type="button"
            wire:click="openTab('list')"
            class="rounded-full px-4 py-2 text-sm {{ $tab === 'list' ? 'bg-brand-700 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50 dark:bg-ink-900 dark:text-ink-300 dark:ring-ink-700' }}"
        >
            List
        </button>
        <button
            type="button"
            wire:click="openTab('create')"
            class="rounded-full px-4 py-2 text-sm {{ $tab === 'create' ? 'bg-brand-700 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50 dark:bg-ink-900 dark:text-ink-300 dark:ring-ink-700' }}"
        >
            {{ $todayReport && ! $todayReport->status?->isDone() ? "Edit today's report" : "Create today's report" }}
        </button>
    </div>

    @if ($tab === 'analytics')
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <p class="text-sm text-ink-500">This week</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $growth->weekWritten }} / {{ $growth->weekExpected }}</p>
                <p class="mt-1 text-xs text-ink-400">Working days written</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-ink-500">Marked done</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $growth->weekDone }}</p>
                <p class="mt-1 text-xs text-ink-400">Accepted this week</p>
            </div>
            <div class="card p-5">
                <p class="text-sm text-ink-500">Streak</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $growth->streak }}</p>
                <p class="mt-1 text-xs text-ink-400">{{ $growth->streak === 1 ? 'working day' : 'working days' }} in a row</p>
            </div>
        </div>

        <section class="card">
            <div class="border-b border-ink-100 px-5 py-3 dark:border-ink-800">
                <h2 class="font-semibold">Last 4 weeks</h2>
                <p class="text-sm text-ink-500">Office holidays and weekly offs are skipped. Click a filled day to read it.</p>
            </div>
            <x-work-week-calendar :days="$growth->calendar" />
        </section>

        <section class="card p-5">
            <h2 class="font-semibold">This month</h2>
            <p class="mt-1 text-sm text-ink-500">{{ $growth->monthWritten }} of {{ $growth->monthExpected }} working days written · {{ $growth->monthDone }} marked done</p>
            @if ($growth->growthNotes !== [])
                <ul class="mt-4 space-y-3">
                    @foreach ($growth->growthNotes as $note)
                        <li>
                            <p class="text-xs font-medium text-ink-400">{{ $note['label'] }}</p>
                            <p class="text-sm text-ink-700 dark:text-ink-200">{{ $note['note'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm text-ink-400">Add a short note when you save: what you are better at than last week.</p>
            @endif
        </section>
    @elseif ($tab === 'create')
        <form wire:submit="save" class="card max-w-3xl space-y-4 p-6">
            <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit report' : "Today's report" }}</h2>
            <div>
                <label class="label">Date</label>
                <input type="date" wire:model="date" class="input" @disabled($editingId)>
                @error('date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">What did you do today?</label>
                <textarea wire:model="accomplished" rows="8" class="input" placeholder="Write in simple points. Learning, calls, and office work are all fine."></textarea>
                @error('accomplished')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Still pending? (optional)</label>
                <textarea wire:model="pending" rows="3" class="input"></textarea>
            </div>
            <div>
                <label class="label">What did you learn? (optional)</label>
                <textarea wire:model="learned" rows="2" class="input"></textarea>
            </div>
            <div>
                <label class="label">What are you better at than last week? (optional)</label>
                <textarea wire:model="growthNote" rows="2" class="input" placeholder="Git, calling, Laravel routes…"></textarea>
                @error('growthNote')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="btn-secondary" wire:click="openTab('list')">Back to list</button>
                <button type="submit" class="btn-primary">Save for review</button>
            </div>
        </form>
    @elseif ($tab === 'show' && $reading)
        <div class="card max-w-3xl p-6">
            <x-work-report-reader :report="$reading">
                @can('update', $reading)
                    <button type="button" class="btn-primary" wire:click="edit('{{ $reading->id }}')">Edit</button>
                @endcan
                <button type="button" class="btn-secondary" wire:click="openTab('list')">Back to list</button>
                @if ($reading->status->isDone() && $reading->reviewer)
                    <p class="text-sm text-ink-500">Marked done by {{ $reading->reviewer->name }}{{ $reading->reviewed_at ? ' on '.$reading->reviewed_at->format('d M Y') : '' }}.</p>
                @endif
            </x-work-report-reader>
        </div>
    @else
        <p class="text-sm text-ink-500">Your reports go to your manager for review after you save.</p>
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Progress</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                        @forelse ($reports as $report)
                            <tr wire:key="{{ $report->id }}">
                                <td class="px-4 py-3 font-medium">{{ $report->work_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $report->preview() }}</td>
                                <td class="px-4 py-3"><x-badge :tone="$report->status->tone()">{{ $report->status->label() }}</x-badge></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="text-sm font-medium text-brand-700" wire:click="read('{{ $report->id }}')">Show</button>
                                    @can('update', $report)
                                        <button type="button" class="ml-3 text-sm font-medium text-brand-700" wire:click="edit('{{ $report->id }}')">Edit</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty-state title="No reports yet" description="Write today's progress. It will show up here after you save.">
                                        @if ($canCreateToday)
                                            <button type="button" wire:click="createToday" class="btn-primary">Create today's report</button>
                                        @endif
                                    </x-empty-state>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="border-t border-ink-100 px-4 py-3 dark:border-ink-800">{{ $reports->links() }}</div>
            @endif
        </div>
    @endif
</div>
