@extends('layouts.client-guest')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <p class="text-sm font-semibold text-brand-700">{{ company_name() }}</p>
            <h1 class="mt-2 text-2xl font-semibold">Reset your password</h1>
            <p class="mt-2 text-sm text-ink-500">Enter the email on your portal account.</p>

            <form method="POST" action="{{ route('client.password.email') }}" class="card mt-8 space-y-5 p-6">
                @csrf
                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input">
                    @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary w-full">Send reset link</button>
                <p class="text-center text-sm"><a href="{{ route('client.login') }}" class="text-brand-700 hover:underline">Back to sign in</a></p>
            </form>
        </div>
    </div>
@endsection
