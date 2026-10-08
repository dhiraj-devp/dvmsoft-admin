@extends('layouts.app')

@section('content')
    <x-page-header title="Team progress" description="See who is consistent, read reports, and mark them good or follow-up." :breadcrumbs="['Work' => route('work.my'), 'Team progress' => null]" />
    <livewire:work.team-progress />
@endsection
