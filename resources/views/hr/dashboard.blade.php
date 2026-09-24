@extends('layouts.app')

@section('content')
    <x-page-header title="HR overview" description="People operations for {{ company_name() }}." :breadcrumbs="['HR' => route('hr.dashboard'), 'Overview' => null]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-ink-500">{{ $metric['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-brand-50 p-2.5 text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                        <x-icon :name="$metric['icon']" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink-400">{{ $metric['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Pending leave</h2></div>
            @forelse ($pendingLeave as $request)
                <a href="{{ route('leave.index') }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span>
                        <span class="block font-medium">{{ $request->employee?->name() }}</span>
                        <span class="text-xs text-ink-500">{{ $request->leaveType?->name }} · {{ $request->days }} day(s)</span>
                    </span>
                    <x-badge tone="warning">Pending</x-badge>
                </a>
            @empty
                <x-empty-state title="No pending leave" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Upcoming joiners</h2></div>
            @forelse ($upcomingJoiners as $employee)
                <a href="{{ route('employees.show', $employee) }}" class="flex items-center justify-between px-5 py-3 text-sm hover:bg-ink-50 dark:hover:bg-ink-800/40">
                    <span class="font-medium">{{ $employee->name() }}</span>
                    <span class="text-xs text-ink-500">{{ $employee->user?->date_of_joining?->format('d M') }}</span>
                </a>
            @empty
                <x-empty-state title="No upcoming joiners" />
            @endforelse
        </section>
        <section class="card">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800"><h2 class="font-semibold">Recent HR activity</h2></div>
            @forelse ($recentActivity as $log)
                <div class="px-5 py-3 text-sm">
                    <span class="font-medium">{{ $log->user?->name ?: 'System' }}</span>
                    <span class="text-ink-500"> {{ $log->action }} {{ $log->module }}</span>
                </div>
            @empty
                <x-empty-state title="No HR activity yet" />
            @endforelse
        </section>
    </div>
@endsection
