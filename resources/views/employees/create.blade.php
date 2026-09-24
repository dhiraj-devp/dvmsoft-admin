@extends('layouts.app')

@section('content')
    <x-page-header title="New employee" description="Create the HR record. Login accounts stay managed under Administration → Users." :breadcrumbs="['HR' => route('hr.dashboard'), 'Employees' => route('employees.index'), 'Create' => null]" />
    <livewire:employees.form />
@endsection
