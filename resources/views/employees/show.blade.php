@php
    $tabs = [
        'overview' => 'Overview',
        'documents' => 'Documents',
        'leave' => 'Leave',
        'assets' => 'Assets',
        'onboarding' => 'Onboarding',
        'offboarding' => 'Offboarding',
    ];
    $user = $employee->user;
@endphp

@extends('layouts.app')

@section('content')
    <x-page-header title="{{ $employee->name() }}" description="{{ $employee->code() }} · {{ $user?->job_title ?: 'No title' }}" :breadcrumbs="['HR' => route('hr.dashboard'), 'Employees' => route('employees.index'), $employee->name() => null]">
        <x-slot:actions>
            @can('update', $employee)
                <a href="{{ route('employees.edit', $employee) }}" class="btn-secondary">Edit employee</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap gap-2 overflow-x-auto">
        @foreach ($tabs as $key => $label)
            @php
                $canTab = match ($key) {
                    'documents' => auth()->user()->hasPermission('employee_documents.view'),
                    'leave' => auth()->user()->hasPermission('leave.view'),
                    'assets' => auth()->user()->hasPermission('assets.view'),
                    'onboarding' => auth()->user()->hasPermission('onboarding.view'),
                    'offboarding' => auth()->user()->hasPermission('offboarding.view'),
                    default => true,
                };
            @endphp
            @if ($canTab)
                <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => $key]) }}"
                   class="rounded-full px-4 py-2 text-sm {{ $tab === $key ? 'bg-brand-700 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50 dark:bg-ink-900 dark:text-ink-300 dark:ring-ink-700' }}">
                    {{ $label }}
                </a>
            @endif
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card p-5 xl:col-span-2">
                <div class="flex items-start gap-4">
                    @if ($employee->photo_path)
                        <img src="{{ route('employees.photo', $employee) }}" alt="" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-ink-200">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 font-semibold text-brand-800">{{ $user?->initials() }}</div>
                    @endif
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <x-badge :tone="$employee->employment_status->tone()">{{ $employee->employment_status->label() }}</x-badge>
                            <x-badge :tone="$employee->probation_status->tone()">{{ $employee->probation_status->label() }}</x-badge>
                        </div>
                        <p class="mt-2 text-sm text-ink-500">{{ $user?->email }} · {{ $user?->phone ?: 'No phone' }}</p>
                    </div>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
                    <div><dt class="text-ink-500">Employee code</dt><dd class="font-medium">{{ $employee->code() ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Department</dt><dd class="font-medium">{{ $user?->department?->name ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Manager</dt><dd class="font-medium">{{ $user?->manager?->name ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Employment type</dt><dd class="font-medium">{{ $employee->employment_type->label() }}</dd></div>
                    <div><dt class="text-ink-500">Joining date</dt><dd class="font-medium">{{ $user?->date_of_joining?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Probation end</dt><dd class="font-medium">{{ $employee->probation_end_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Confirmation</dt><dd class="font-medium">{{ $employee->confirmation_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Exit date</dt><dd class="font-medium">{{ $employee->exit_date?->format(settings('company.date_format', 'd M Y')) ?: '—' }}</dd></div>
                    <div><dt class="text-ink-500">Exit reason</dt><dd class="font-medium">{{ $employee->exit_reason ?: '—' }}</dd></div>
                </dl>
            </section>
            <section class="card p-5 text-sm">
                <h2 class="font-semibold">Emergency contact</h2>
                <p class="mt-3 font-medium">{{ $employee->emergency_contact_name ?: '—' }}</p>
                <p class="text-ink-500">{{ $employee->emergency_contact_phone ?: '—' }}</p>
                <h2 class="mt-6 font-semibold">Address</h2>
                <p class="mt-2 text-ink-600">{{ $employee->address ?: '—' }}</p>
                @if ($employee->notes)
                    <p class="mt-4 rounded-xl bg-ink-50 p-3 dark:bg-ink-950">{{ $employee->notes }}</p>
                @endif
            </section>
        </div>
    @elseif ($tab === 'documents')
        <livewire:employee-documents.index :employee-id="$employee->id" :key="'docs-'.$employee->id" />
    @elseif ($tab === 'leave')
        <livewire:leave.index :employee-id="$employee->id" :key="'leave-'.$employee->id" />
    @elseif ($tab === 'assets')
        <livewire:assets.index :employee-id="$employee->id" :key="'assets-'.$employee->id" />
    @elseif ($tab === 'onboarding')
        <livewire:hr-checklists.index type="onboarding" :employee-id="$employee->id" :key="'on-'.$employee->id" />
    @else
        <livewire:hr-checklists.index type="offboarding" :employee-id="$employee->id" :key="'off-'.$employee->id" />
    @endif
@endsection
