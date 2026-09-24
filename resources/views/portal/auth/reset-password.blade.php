@extends('layouts.client-guest')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <p class="text-sm font-semibold text-brand-700">{{ company_name() }}</p>
            <h1 class="mt-2 text-2xl font-semibold">Choose a new password</h1>

            <form method="POST" action="{{ route('client.password.update') }}" class="card mt-8 space-y-5 p-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="input">
                    @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="label">New password</label>
                    <input id="password" name="password" type="password" required class="input">
                    @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="input">
                </div>
                <button type="submit" class="btn-primary w-full">Update password</button>
            </form>
        </div>
    </div>
@endsection
