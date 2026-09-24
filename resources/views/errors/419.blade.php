@extends('layouts.guest')

@section('title', 'Session expired')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="card max-w-lg p-8 text-center">
            <p class="text-sm font-semibold text-brand-700">419</p>
            <h1 class="mt-2 text-2xl font-semibold">Your session expired</h1>
            <p class="mt-2 text-sm text-ink-500">Refresh the page and try again. This protects the application from stale form submissions.</p>
            <a href="{{ url()->previous() }}" class="btn-primary mt-6">Try again</a>
        </div>
    </div>
@endsection
