@extends('layouts.app')

@section('content')
    <x-page-header title="Payments" description="Record collections and keep invoice balances current." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Payments' => null]" />
    <livewire:payments.index :invoice-id="request('invoice_id')" />
@endsection
