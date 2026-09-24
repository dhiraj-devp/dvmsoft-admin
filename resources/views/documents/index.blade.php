@extends('layouts.app')

@section('content')
    <x-page-header title="All documents" description="Search and filter the company document registry." :breadcrumbs="['Documents' => route('documents.dashboard'), 'All Documents' => null]">
        <x-slot:actions>
            @can('create', App\Models\Document::class)
                <a href="{{ route('documents.create') }}" class="btn-primary">New document</a>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <livewire:documents.index />
@endsection
