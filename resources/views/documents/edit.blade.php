@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $document->number }}" :breadcrumbs="['Documents' => route('documents.dashboard'), 'All Documents' => route('documents.index'), $document->number => route('documents.show', $document), 'Edit' => null]" />
    <livewire:documents.form :document="$document" :key="$document->id" />
@endsection
