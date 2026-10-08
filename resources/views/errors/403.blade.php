@extends('layouts.guest')

@section('title', 'Access denied')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-6">
        <div class="card max-w-lg p-8 text-center">
            <p class="text-sm font-semibold text-brand-700">403</p>
            <h1 class="mt-2 text-2xl font-semibold">You don’t have access</h1>
            <p class="mt-2 text-sm text-ink-500">This area is limited by your assigned permissions. Ask an administrator if you need access.</p>
            <a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : route('login') }}" class="btn-primary mt-6">Go back</a>
        </div>
    </div>
@endsection
