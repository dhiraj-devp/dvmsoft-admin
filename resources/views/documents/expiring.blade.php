@extends('layouts.app')

@section('content')
    <x-page-header title="Expiring documents" description="Documents approaching or past their expiry date." :breadcrumbs="['Documents' => route('documents.dashboard'), 'Expiring' => null]" />
    <livewire:documents.expiring />
@endsection
