@extends('layouts.app')

@section('content')
    <x-page-header title="Users" description="Manage employees, access, and reporting lines." :breadcrumbs="['Administration' => route('users.index'), 'Users' => null]" />
    <livewire:users.index />
@endsection
