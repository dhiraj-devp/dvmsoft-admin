@extends('layouts.app')

@section('content')
    <x-page-header title="Employees" description="People records, employment, and reporting lines." :breadcrumbs="['HR' => route('hr.dashboard'), 'Employees' => null]">
        <x-slot:actions>
            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn-primary">New employee</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <livewire:employees.index />
@endsection
