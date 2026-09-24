@extends('layouts.app')

@section('content')
    <x-page-header
        title="Automations"
        description="Central workflow engine for reminders, SLA, documents, and client portal events."
        :breadcrumbs="['Administration' => route('automations.index'), 'Automations' => null]"
    />

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

    <div class="mt-6">
        <livewire:automations.index />
    </div>
@endsection
