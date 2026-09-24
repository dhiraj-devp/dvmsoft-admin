@extends('layouts.app')

@section('content')
    <x-page-header title="Contacts" description="People attached to client accounts." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Contacts' => null]" />
    <livewire:contacts.index />
@endsection
