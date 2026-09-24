@extends('layouts.guest')

@section('title', 'Staff sign in')

@section('content')
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.25),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(15,118,110,0.2),transparent_35%)]"></div>
            <div class="relative">
                <p class="text-sm font-semibold tracking-wide text-brand-300">{{ company_name() }} · Admin / Staff</p>
                <h1 class="mt-6 max-w-md text-4xl font-semibold tracking-tight text-white">Dvmsoft Admin OS</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-ink-300">Internal staff access for people, permissions, settings, and the modules that run Dvmsoft.</p>
            </div>
            <div class="relative grid gap-4 text-sm text-ink-300">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Secure internal access with granular RBAC.</div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Audit-ready actions from the first login.</div>
            </div>
        </div>

        <div class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-brand-700">{{ company_name() }}</p>
                    <h2 class="mt-2 text-2xl font-semibold">Sign in to Dvmsoft Admin</h2>
                    <p class="mt-2 text-sm text-ink-500">Use your work email to continue.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="card space-y-5 p-6">
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
                    <button type="submit" class="btn-primary w-full">Continue</button>
                </form>
            </div>
        </div>
    </div>
@endsection
