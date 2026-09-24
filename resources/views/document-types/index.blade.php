@extends('layouts.app')

@section('content')
    <x-page-header title="Document types" description="Configurable types for the company document registry." :breadcrumbs="['Documents' => route('documents.dashboard'), 'Document Types' => null]" />
    <livewire:document-types.index />
@endsection
