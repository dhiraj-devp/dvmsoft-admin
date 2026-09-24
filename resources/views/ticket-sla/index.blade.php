@extends('layouts.app')

@section('content')
    <x-page-header title="SLA rules" description="Response windows by ticket priority." :breadcrumbs="['Support' => route('support.dashboard'), 'SLA' => null]" />
    <livewire:ticket-sla.index />
@endsection
