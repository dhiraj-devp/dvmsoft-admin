@extends('layouts.client-guest')

@section('content')
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.25),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(15,118,110,0.2),transparent_35%)]"></div>
            <div class="relative">
                <p class="text-sm font-semibold tracking-wide text-brand-300">{{ company_name() }} · Client Portal</p>
                <h1 class="mt-6 max-w-md text-4xl font-semibold tracking-tight text-white">Dvmsoft Client Portal</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-ink-300">Projects, documents, quotations, invoices, and support — only for your account.</p>
            </div>
            <div class="relative grid gap-4 text-sm text-ink-300">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Track project progress and approved requirements.</div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Review quotations, invoices, and payment history.</div>
            </div>
        </div>

        <div class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-brand-700">{{ company_name() }}</p>
                    <h2 class="mt-2 text-2xl font-semibold">Sign in to the Dvmsoft Client Portal</h2>
                    <p class="mt-2 text-sm text-ink-500">Use the email your account manager invited. This is not staff access.</p>
                </div>

                <form method="POST" action="{{ route('client.login.store') }}" class="card space-y-5 p-6" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div>
                        <label for="email" class="label">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input">
                        @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password" class="label">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-ink-600">
                        <input type="checkbox" name="remember" class="rounded border-ink-300 text-brand-700 focus:ring-brand-500">
                        Remember this device
                    </label>
                    <button type="submit" class="btn-primary w-full" :disabled="loading">
                        <span x-show="!loading">Continue</span>
                        <span x-cloak x-show="loading">Signing in…</span>
                    </button>
                    <p class="text-center text-sm">
                        <a href="{{ route('client.password.request') }}" class="text-brand-700 hover:underline">Forgot password?</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
@endsection
