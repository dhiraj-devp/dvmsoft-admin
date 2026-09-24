@extends('layouts.app')

@section('content')
    <x-page-header title="New ticket" description="Link the ticket to an existing client and optional project." :breadcrumbs="['Support' => route('support.dashboard'), 'Tickets' => route('tickets.index'), 'Create' => null]" />
    <livewire:tickets.form />
@endsection
