@extends('layouts.client-guest')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <p class="text-sm font-semibold text-brand-700">{{ company_name() }}</p>
            <h1 class="mt-2 text-2xl font-semibold">Verify your email</h1>
            <p class="mt-2 text-sm text-ink-500">We sent a verification link to {{ auth('client')->user()?->email }}. Open it to continue.</p>

            <form method="POST" action="{{ route('client.verification.send') }}" class="card mt-8 space-y-4 p-6">
                @csrf
                <button type="submit" class="btn-primary w-full">Resend verification email</button>
            </form>
            <form method="POST" action="{{ route('client.logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm text-ink-500 hover:text-ink-800">Sign out</button>
            </form>
        </div>
    </div>
@endsection
