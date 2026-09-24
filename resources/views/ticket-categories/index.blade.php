@extends('layouts.app')

@section('content')
    <x-page-header title="Ticket categories" description="Configurable types for the support queue." :breadcrumbs="['Support' => route('support.dashboard'), 'Categories' => null]" />
    <livewire:ticket-categories.index />
@endsection
