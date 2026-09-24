@extends('layouts.app')

@section('content')
    <x-page-header
        title="AI company risk summary"
        description="Internal assistance over existing Dvmsoft records. Nothing is duplicated or changed automatically."
        :breadcrumbs="['AI' => route('ai.overview'), 'Overview' => null]"
    />
    <livewire:ai.company-overview />
@endsection
