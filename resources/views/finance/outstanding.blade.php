@extends('layouts.app')

@section('content')
    <x-page-header title="Outstanding receivables" description="Open invoices, paid amounts, and days overdue." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Outstanding' => null]" />
    <livewire:finance.outstanding />
@endsection
