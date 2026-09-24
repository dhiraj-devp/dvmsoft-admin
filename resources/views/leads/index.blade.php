@extends('layouts.app')

@section('content')
    <x-page-header title="Leads" description="Capture demand and move it through the pipeline." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Leads' => null]" />
    <livewire:leads.index />
@endsection
