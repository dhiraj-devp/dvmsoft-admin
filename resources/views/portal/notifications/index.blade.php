@extends('layouts.client')

@section('content')
    <x-page-header title="Notifications" description="Updates about your projects, documents, invoices, and tickets." :breadcrumbs="['Notifications' => null]">
        <x-slot:actions>
            <a href="{{ route('client.notifications.preferences') }}" class="btn-secondary">Preferences</a>
        </x-slot:actions>
    </x-page-header>
    <livewire:notifications.inbox guard="client" />
@endsection
