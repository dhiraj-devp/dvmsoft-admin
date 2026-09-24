@extends('layouts.app')

@section('content')
    <x-page-header title="Clients" description="Accounts converted from demand or added directly." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Clients' => null]" />
    <livewire:clients.index />
@endsection
