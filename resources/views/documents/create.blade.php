@extends('layouts.app')

@section('content')
    <x-page-header title="New document" description="Files are stored privately and downloaded only after authorization." :breadcrumbs="['Documents' => route('documents.dashboard'), 'All Documents' => route('documents.index'), 'Create' => null]" />
    <livewire:documents.form />
@endsection
