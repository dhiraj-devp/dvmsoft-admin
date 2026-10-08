@extends('layouts.app')

@section('content')
    <x-page-header title="Work Reports" description="Consistency of daily updates, open goals, and progress overviews." :breadcrumbs="['Work' => route('work.my'), 'Reports' => null]" />
    <livewire:work.reports />
@endsection
