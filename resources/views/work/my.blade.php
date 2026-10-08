@extends('layouts.app')

@section('content')
    <x-page-header title="Daily progress" description="Write today, then watch your streak and calendar grow." :breadcrumbs="['Work' => route('work.my'), 'Daily progress' => null]" />
    <livewire:work.my-work />
@endsection
