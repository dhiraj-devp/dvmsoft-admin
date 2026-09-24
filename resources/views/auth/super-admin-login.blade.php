@extends('layouts.guest')

@section('title', 'Restricted sign in')

@section('content')
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.22),transparent_40%),radial-gradient(circle_at_bottom_left,rgba(15,23,42,0.9),transparent_35%)]"></div>
            <div class="relative">
                <p class="text-sm font-semibold tracking-wide text-amber-300">{{ company_name() }} · Restricted</p>
                <h1 class="mt-6 max-w-md text-4xl font-semibold tracking-tight text-white">Control plane</h1>
                <p class="mt-4 max-w-md text-sm leading-6 text-ink-300">Privileged operators only. This sign-in is not part of the staff workspace.</p>
            </div>
            <div class="relative grid gap-4 text-sm text-ink-300">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Server-side authorization on every privileged route.</div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4">Isolated from staff and client authentication entry points.</div>
            </div>
        </div>

        <div class="flex items-center justify-center bg-ink-950 px-6 py-12 lg:bg-ink-50 dark:bg-ink-950">
            <div class="w-full max-w-md">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ company_name() }}</p>
                    <h2 class="mt-2 text-2xl font-semibold text-white lg:text-ink-900 dark:text-white">Restricted operator sign in</h2>
                    <p class="mt-2 text-sm text-ink-400 lg:text-ink-500">Use your operator credentials.</p>
                </div>

                <form method="POST" action="{{ route('super-admin.login.store') }}" class="card space-y-5 p-6">
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
                        <input type="checkbox" name="remember" class="rounded border-ink-300 text-amber-700 focus:ring-amber-500">
                        Remember this device
                    </label>
                    <button type="submit" class="btn-primary w-full bg-ink-900 hover:bg-ink-800">Continue</button>
                </form>
            </div>
        </div>
    </div>
@endsection
