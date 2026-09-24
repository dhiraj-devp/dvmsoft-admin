@extends('layouts.app')

@section('content')
    <x-page-header title="New project" description="Start delivery work for a client." :breadcrumbs="['Projects' => route('projects.dashboard'), 'All Projects' => route('projects.index'), 'Create' => null]" />
    <livewire:projects.form />
@endsection
