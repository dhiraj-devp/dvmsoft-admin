@extends('layouts.app')

@section('content')
    <x-page-header title="Dashboard" description="A live snapshot of Dvmsoft operations." :breadcrumbs="['Home' => route('dashboard'), 'Dashboard' => null]" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($metrics as $metric)
            <div class="card p-5">
                    @if (! empty($metric['url']))
                        <a href="{{ $metric['url'] }}" class="block">
                    @endif
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-ink-500">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-3xl font-semibold tracking-tight">{{ $metric['value'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-brand-50 p-2.5 text-brand-700 dark:bg-brand-950 dark:text-brand-200">
                            <x-icon :name="$metric['icon']" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-ink-400">{{ $metric['hint'] }}</p>
                    @if (! empty($metric['url']))
                        </a>
                    @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="border-b border-ink-100 px-5 py-4 dark:border-ink-800">
                <h2 class="font-semibold">Recent activity</h2>
            </div>
            @if ($activity->isEmpty())
                <x-empty-state title="No activity yet" description="Sensitive actions will appear here as the team starts using Admin OS." />
            @else
                <ul class="divide-y divide-ink-100 dark:divide-ink-800">
                    @foreach ($activity as $log)
                        <li class="flex items-start justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="text-sm font-medium">{{ $log->user?->name ?? 'System' }} · {{ $log->action }}</p>
                                <p class="text-xs text-ink-500">{{ ucfirst($log->module) }} @if($log->auditable_id) · {{ $log->auditable_id }} @endif</p>
                            </div>
                            <span class="text-xs text-ink-400">{{ $log->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="font-semibold">Quick actions</h2>
                <div class="mt-4 grid gap-2">
                    @can('leads.view')
                        <a href="{{ route('leads.index') }}" class="btn-secondary justify-start">View leads</a>
                    @endcan
                    @can('projects.view')
                        <a href="{{ route('projects.index') }}" class="btn-secondary justify-start">Open projects</a>
                    @endcan
                    @can('invoices.view')
                        <a href="{{ route('invoices.index') }}" class="btn-secondary justify-start">Open invoices</a>
                    @endcan
                    @can('users.create')
                        <a href="{{ route('users.index') }}" class="btn-secondary justify-start">Add a user</a>
                    @endcan
                    @can('roles.view')
                        <a href="{{ route('roles.index') }}" class="btn-secondary justify-start">Review roles</a>
                    @endcan
                    @can('settings.view')
                        <a href="{{ route('settings.index') }}" class="btn-secondary justify-start">Company settings</a>
                    @endcan
                    @can('audit_logs.view')
                        <a href="{{ route('audit-logs.index') }}" class="btn-secondary justify-start">Open audit logs</a>
                    @endcan
                </div>
            </section>

            <section class="card p-5">
                <h2 class="font-semibold">System status</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-500">Application</dt>
                        <dd class="font-medium text-emerald-600">Operational</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-500">People</dt>
                        <dd class="font-medium">{{ $userCount }} users</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-500">Queue</dt>
                        <dd class="font-medium">{{ config('queue.default') }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-ink-500">Environment</dt>
                        <dd class="font-medium">{{ app()->environment() }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
@endsection
