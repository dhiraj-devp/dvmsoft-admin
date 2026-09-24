@extends('layouts.app')

@section('content')
    <x-page-header title="Invoices" description="Bill clients, track GST, and collect payment." :breadcrumbs="['Finance' => route('finance.dashboard'), 'Invoices' => null]">
        <x-slot:actions>
            @can('create', App\Models\Invoice::class)
                <a href="{{ route('invoices.create') }}" class="btn-primary">New invoice</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <livewire:invoices.index />
@endsection
