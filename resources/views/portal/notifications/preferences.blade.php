@extends('layouts.client')

@section('content')
    <x-page-header title="Notification preferences" description="Choose how you receive portal updates." :breadcrumbs="['Notifications' => route('client.notifications.index'), 'Preferences' => null]" />

    <form method="POST" action="{{ route('client.notifications.preferences.update') }}" class="card max-w-xl space-y-4 p-6">
        @csrf
        @method('PUT')
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="in_app_enabled" value="0">
            <input type="checkbox" name="in_app_enabled" value="1" class="rounded border-ink-300 text-brand-700" @checked($preference->in_app_enabled)>
            In-app notifications
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="email_enabled" value="0">
            <input type="checkbox" name="email_enabled" value="1" class="rounded border-ink-300 text-brand-700" @checked($preference->email_enabled)>
            Email notifications
        </label>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save preferences</button>
        </div>
    </form>
@endsection
