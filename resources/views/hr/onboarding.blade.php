@extends('layouts.app')

@section('content')
    <x-page-header title="Onboarding" description="New-hire checklist for accounts, documents, assets, and access." :breadcrumbs="['HR' => route('hr.dashboard'), 'Onboarding' => null]" />
    <livewire:hr-checklists.index type="onboarding" />
@endsection
