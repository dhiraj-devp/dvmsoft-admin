@extends('layouts.app')

@section('content')
    <x-page-header title="Reviews" description="Feedback and actions on daily updates." :breadcrumbs="['Work' => route('work.my'), 'Reviews' => null]" />
    <livewire:work.reviews />
@endsection
