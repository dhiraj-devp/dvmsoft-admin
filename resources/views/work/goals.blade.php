@extends('layouts.app')

@section('content')
    <x-page-header title="Goals" description="Outcomes and deadlines. Staff decide the daily steps — managers do not create daily tasks." :breadcrumbs="['Work' => route('work.my'), 'Goals' => null]" />
    <livewire:work.goals />
    <div class="mt-8">
        <h2 class="mb-3 text-lg font-semibold">Learning goals</h2>
        <livewire:work.learning-goals />
    </div>
@endsection
