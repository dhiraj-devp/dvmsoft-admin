@extends('layouts.app')

@section('content')
    <x-page-header title="Edit {{ $project->number }}" :breadcrumbs="['Projects' => route('projects.dashboard'), $project->name => route('projects.show', $project), 'Edit' => null]" />
    <livewire:projects.form :project="$project" :key="$project->id" />
@endsection
