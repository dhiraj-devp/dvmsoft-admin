@extends('layouts.client')

@section('content')
    <x-page-header title="Profile" description="Your portal login details. Company account information is managed by Dvmsoft." />

    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('client.profile.update') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <div>
                <label class="label">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required class="input">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Email</label>
                <input value="{{ $user->email }}" disabled class="input bg-ink-50 dark:bg-ink-900">
            </div>
            <div>
                <label class="label">Company</label>
                <input value="{{ $user->client?->name }}" disabled class="input bg-ink-50 dark:bg-ink-900">
            </div>
            <button class="btn-primary">Save profile</button>
        </form>

        <form method="POST" action="{{ route('client.profile.password') }}" class="card space-y-4 p-6">
            @csrf
            @method('PUT')
            <div>
                <label class="label">Current password</label>
                <input type="password" name="current_password" required class="input">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">New password</label>
                <input type="password" name="password" required class="input">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Confirm password</label>
                <input type="password" name="password_confirmation" required class="input">
            </div>
            <button class="btn-primary">Update password</button>
        </form>
    </div>
@endsection
