@extends('layouts.app')

@section('content')
    <x-page-header title="Roles & permissions" description="Assign granular access without hardcoding role names in the application." :breadcrumbs="['Administration' => route('roles.index'), 'Roles' => null]" />
    <livewire:roles.index />
@endsection
