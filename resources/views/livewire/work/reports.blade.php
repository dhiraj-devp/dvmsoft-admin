<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-ink-500">Daily updates (14 days)</p>
            <p class="mt-2 text-3xl font-semibold">{{ $updatesThisPeriod }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-500">Open goals</p>
            <p class="mt-2 text-3xl font-semibold">{{ $openGoals }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-ink-500">Overdue goals</p>
            <p class="mt-2 text-3xl font-semibold">{{ $overdueGoals }}</p>
        </div>
    </div>
    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Person</th>
                        <th class="px-4 py-3 font-medium">Progress score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @foreach ($scored as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['user']->name }}</td>
                            <td class="px-4 py-3">{{ $row['score']->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
