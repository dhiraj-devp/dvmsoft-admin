@extends('layouts.app')

@section('content')
    <x-page-header title="Assets" description="Company equipment assigned to people." :breadcrumbs="['HR' => route('hr.dashboard'), 'Assets' => null]" />
    <livewire:assets.index />
@endsection
