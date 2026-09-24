@extends('layouts.app')

@section('content')
    <x-page-header title="Notifications" description="In-app alerts from automations and workflows." :breadcrumbs="['Notifications' => null]">
        <x-slot:actions>
            <a href="{{ route('notifications.preferences') }}" class="btn-secondary">Preferences</a>
        </x-slot:actions>
    </x-page-header>
    <livewire:notifications.inbox />
@endsection
