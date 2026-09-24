<form wire:submit="save" class="card overflow-hidden">
    <div class="table-wrap">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-ink-50 text-xs uppercase text-ink-500 dark:bg-ink-950">
                <tr>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">SLA hours</th>
                    <th class="px-4 py-3">Warn before (hours)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                @foreach ($priorities as $priority)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $priority->label() }}</td>
                        <td class="px-4 py-3">
                            <input type="number" wire:model="rules.{{ $priority->value }}.hours" class="input max-w-[8rem]">
                            @error('rules.'.$priority->value.'.hours')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </td>
                        <td class="px-4 py-3">
                            <input type="number" wire:model="rules.{{ $priority->value }}.warning_hours" class="input max-w-[8rem]">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @can('tickets.manage_sla')
        <div class="flex justify-end border-t border-ink-100 px-4 py-4 dark:border-ink-800">
            <button type="submit" class="btn-primary">Save SLA rules</button>
        </div>
    @endcan
</form>
