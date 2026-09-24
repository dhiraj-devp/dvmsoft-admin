@extends('layouts.app')

@section('content')
    <x-page-header title="Quotations" description="Price work, send it, and convert accepted deals." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Quotations' => null]">
        <x-slot:actions>
            @can('create', App\Models\Quotation::class)
                <a href="{{ route('quotations.create') }}" class="btn-primary">New quotation</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <livewire:quotations.index />
@endsection
