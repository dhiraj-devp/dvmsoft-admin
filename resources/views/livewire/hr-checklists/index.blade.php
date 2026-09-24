<div class="space-y-6">
    @forelse ($rows as $row)
        @php
            $employee = $row['employee'];
            $checklist = $row['checklist'];
        @endphp
        <section class="card p-5">
            @unless ($single)
                <div class="mb-4 flex items-center justify-between">
                    <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => $checklist->type->value]) }}" class="font-semibold text-brand-700">{{ $employee->name() }}</a>
                    <span class="text-sm text-ink-500">{{ $checklist->progressPercent() }}%</span>
                </div>
            @endunless
            <div class="mb-4 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-800">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $checklist->progressPercent() }}%"></div>
            </div>
            <div class="space-y-2">
                @foreach ($checklist->items as $item)
                    <label class="flex items-center justify-between rounded-xl border border-ink-200 px-3 py-2 text-sm dark:border-ink-700">
                        <span>{{ $item->label }}</span>
                        @can($checklist->type->value.'.manage')
                            <input type="checkbox" class="rounded border-ink-300 text-brand-700" @checked($item->is_completed) wire:click="toggle('{{ $item->id }}')">
                        @else
                            <x-badge :tone="$item->is_completed ? 'success' : 'neutral'">{{ $item->is_completed ? 'Done' : 'Open' }}</x-badge>
                        @endcan
                    </label>
                @endforeach
            </div>
        </section>
    @empty
        <x-empty-state title="No checklist records" />
    @endforelse
</div>
