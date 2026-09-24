@extends('layouts.app')

@section('content')
    <x-page-header
        title="AI finance insights"
        description="Collection and spend patterns from existing invoices and expenses. Not accounting or legal advice."
        :breadcrumbs="['AI' => route('ai.overview'), 'Finance insights' => null]"
    />
    <livewire:ai.finance-insights />
@endsection
