@extends('layouts.app')

@section('content')
    <x-page-header title="New invoice" description="Line items, discounts, and GST use the same calculator as quotations." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Invoices' => route('invoices.index'), 'Create' => null]" />
    <livewire:invoices.form />
@endsection
