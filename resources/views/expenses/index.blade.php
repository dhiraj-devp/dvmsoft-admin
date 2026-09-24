@extends('layouts.app')

@section('content')
    <x-page-header title="Expenses" description="Track vendor costs, tax, and project spend." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Expenses' => null]" />
    <livewire:expenses.index />
@endsection
