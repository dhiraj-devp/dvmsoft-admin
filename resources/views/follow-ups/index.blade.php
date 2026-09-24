@extends('layouts.app')

@section('content')
    <x-page-header title="Follow-ups" description="Calls, meetings, and reminders across the pipeline." :breadcrumbs="['Sales' => route('sales.dashboard'), 'Follow-ups' => null]" />
    <livewire:follow-ups.index />
@endsection
