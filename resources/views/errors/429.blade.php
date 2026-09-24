@extends('layouts.guest')

@section('title', 'Too many requests')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="card max-w-lg p-8 text-center">
            <p class="text-sm font-semibold text-brand-700">429</p>
            <h1 class="mt-2 text-2xl font-semibold">Slow down</h1>
            <p class="mt-2 text-sm text-ink-500">Too many attempts were made in a short time. Wait a moment and continue.</p>
            <a href="{{ route('login') }}" class="btn-primary mt-6">Back to sign in</a>
        </div>
    </div>
@endsection
