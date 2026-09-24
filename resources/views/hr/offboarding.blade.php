@extends('layouts.app')

@section('content')
    <x-page-header title="Offboarding" description="Exit checklist for assets, access, and clearance." :breadcrumbs="['HR' => route('hr.dashboard'), 'Offboarding' => null]" />
    <livewire:hr-checklists.index type="offboarding" />
@endsection
