@extends('layouts.app')

@section('content')
    <x-page-header title="All projects" description="Every delivery engagement, linked to clients and quotations." :breadcrumbs="['Projects' => route('projects.dashboard'), 'All Projects' => null]" />
    <livewire:projects.index />
@endsection
