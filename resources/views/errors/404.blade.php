@extends('layouts.guest')

@section('title', 'Page not found')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="card max-w-lg p-8 text-center">
            <p class="text-sm font-semibold text-brand-700">404</p>
            <h1 class="mt-2 text-2xl font-semibold">This page does not exist</h1>
            <p class="mt-2 text-sm text-ink-500">The page may have moved, or the module has not been built yet.</p>
            <a href="{{ url('/') }}" class="btn-primary mt-6">Return home</a>
        </div>
    </div>
@endsection
