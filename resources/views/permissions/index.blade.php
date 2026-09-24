@extends('layouts.app')

@section('content')
    <x-page-header title="Permissions" description="The complete access catalog used across Admin OS." :breadcrumbs="['Administration' => route('permissions.index'), 'Permissions' => null]" />
    <livewire:permissions.index />
@endsection
