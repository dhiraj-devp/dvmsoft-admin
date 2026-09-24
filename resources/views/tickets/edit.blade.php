@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $ticket->number }}" :breadcrumbs="['Support' => route('support.dashboard'), 'Tickets' => route('tickets.index'), $ticket->number => route('tickets.show', $ticket), 'Edit' => null]" />
    <livewire:tickets.form :ticket="$ticket" :key="$ticket->id" />
@endsection
