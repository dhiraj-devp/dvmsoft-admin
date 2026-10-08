@extends('layouts.app')

@section('content')
    <x-page-header title="Daily Updates" description="End-of-day reports: planned vs done, learning, and what needs attention." :breadcrumbs="['Work' => route('work.my'), 'Daily Updates' => null]" />
    <livewire:work.daily-updates />
@endsection
