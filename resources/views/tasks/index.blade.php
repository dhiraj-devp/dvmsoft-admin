@extends('layouts.app')

@section('content')
    <x-page-header title="My tasks" description="Work assigned to you across projects." :breadcrumbs="['Projects' => route('projects.dashboard'), 'My Tasks' => null]" />
    <livewire:tasks.index :mine-only="true" />
@endsection
