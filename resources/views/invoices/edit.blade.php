@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $invoice->number }}" :breadcrumbs="['Finance' => route('finance.dashboard'), 'Invoices' => route('invoices.index'), $invoice->number => route('invoices.show', $invoice), 'Edit' => null]" />
    <livewire:invoices.form :invoice="$invoice" :key="$invoice->id" />
@endsection
