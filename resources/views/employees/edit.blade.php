@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $employee->name() }}" :breadcrumbs="['HR' => route('hr.dashboard'), 'Employees' => route('employees.index'), $employee->name() => route('employees.show', $employee), 'Edit' => null]" />
    <livewire:employees.form :employee="$employee" :key="$employee->id" />
@endsection
