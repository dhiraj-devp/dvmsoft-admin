@extends('layouts.app')

@section('content')
    <x-page-header title="Milestones" description="Delivery checkpoints across all projects." :breadcrumbs="['Projects' => route('projects.dashboard'), 'Milestones' => null]" />
    <livewire:milestones.index />
@endsection
