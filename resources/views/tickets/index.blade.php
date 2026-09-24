@extends('layouts.app')

@section('content')
    <x-page-header title="Tickets" description="Client issues, assignments, and SLA tracking." :breadcrumbs="['Support' => route('support.dashboard'), 'Tickets' => null]">
        <x-slot:actions>
            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}" class="btn-primary">New ticket</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <livewire:tickets.index />
@endsection
