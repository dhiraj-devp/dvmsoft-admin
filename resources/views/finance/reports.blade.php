@extends('layouts.app')

@section('content')
    <x-page-header title="Finance reports" description="Project profitability from budget, invoices, collections, and expenses." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Reports' => null]" />

    <div class="table-wrap">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-ink-50 text-xs uppercase tracking-wide text-ink-500 dark:bg-ink-950">
                    <tr>
                        <th class="px-4 py-3 font-medium">Project</th>
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Budget / revenue</th>
                        <th class="px-4 py-3 font-medium">Invoiced</th>
                        <th class="px-4 py-3 font-medium">Paid</th>
                        <th class="px-4 py-3 font-medium">Expenses</th>
                        <th class="px-4 py-3 font-medium">Estimated profit</th>
                        <th class="px-4 py-3 font-medium">Actual profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100 dark:divide-ink-800">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('projects.show', $row['project']) }}" class="font-medium text-brand-700">{{ $row['project']->number }}</a>
                                <div class="text-xs text-ink-500">{{ $row['project']->name }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $row['project']->client?->name }}</td>
                            <td class="px-4 py-3">{{ money($row['budget']) }}</td>
                            <td class="px-4 py-3">{{ money($row['invoiced']) }}</td>
                            <td class="px-4 py-3">{{ money($row['paid']) }}</td>
                            <td class="px-4 py-3">{{ money($row['expenses']) }}</td>
                            <td class="px-4 py-3">{{ money($row['estimated_profit']) }}</td>
                            <td class="px-4 py-3 font-medium">{{ money($row['actual_profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No projects yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
