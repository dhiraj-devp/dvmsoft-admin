@extends('layouts.guest')

@section('title', 'Something went wrong')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="card max-w-lg p-8 text-center">
            <p class="text-sm font-semibold text-brand-700">500</p>
            <h1 class="mt-2 text-2xl font-semibold">Something went wrong</h1>
            <p class="mt-2 text-sm text-ink-500">An unexpected error occurred. The team can review the application log if this continues.</p>
            <a href="{{ url('/') }}" class="btn-primary mt-6">Return home</a>
        </div>
    </div>
@endsection
