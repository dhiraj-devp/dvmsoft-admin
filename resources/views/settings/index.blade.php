@extends('layouts.app')

@section('content')
    <x-page-header title="Settings" description="Company identity, branding, localization, and security controls." :breadcrumbs="['Administration' => route('settings.index'), 'Settings' => null]" />
    <livewire:settings.manager :section="$section" :key="$section" />
@endsection
